#!/usr/bin/env python
# -*- coding: utf-8 -*-
# PACKAGE REQUIREMENT:
# - libipmeta 3.0+
# - pyipmeta 3.0+
# - psycopg2
# - python-radix
# - requests
# - pywandio==0.1

import argparse
import csv
import logging
import os
import re
import urlparse
from StringIO import StringIO
import multiprocessing
import psycopg2
import pyipmeta
import radix
import requests
import wandio

ipm = None
GEO_PFX = 'geo.netacuity'


def ipmeta_lookup(prefix):
    """
    ipmeta lookup function used in multi-threaded execution

    :param prefix: IP prefix to look up
    :return: prefix and geolocation
    """
    global ipm
    geoloc = ipm.lookup(prefix)[0]
    country = geoloc["country_code"]
    continent = geoloc["continent_code"]
    (regionid, countyid) = geoloc["polygon_ids"]

    # build continent, country, region, county fqids
    cont_fqid = '.'.join([GEO_PFX, continent])
    country_fqid = '.'.join([cont_fqid, country])
    region_fqid = '.'.join([country_fqid, str(regionid)])
    county_fqid = '.'.join([region_fqid, str(countyid)])

    pfxgeo = set()
    pfxgeo.add(cont_fqid)
    pfxgeo.add(country_fqid)
    pfxgeo.add(region_fqid)
    pfxgeo.add(county_fqid)
    return prefix, pfxgeo


class MddbUpdater:
    """
    IODA metadata database (MDDB) updater.

    This script takes in metadata files, like pfx2as and geolocation data, and commit it to a metadata database.
    """

    def __init__(self):
        self.FQID_TO_ID = {}
        self.NEXT_ID = 0
        self.COUNTRY_NAMES = {}
        self.REGION_NAMES = {}
        self.ASN_INFO = {}

        # entity types
        self.types = {}

        # entity type and attribute id counter
        self.next_type_id = 0
        self.next_attr_id = 0

        # database rows saved as lists
        self.rows_entities = []
        self.rows_types = []
        self.rows_attributes = []
        self.rows_relationships = []

    @staticmethod
    def _copy_into_table(conn, cur, table, columns, rows):
        """
        Bulk write table rows into database using COPY operation.

        :param conn: database connection
        :param cur: database connection cursor
        :param table: table name
        :param columns: columns in table
        :param rows: data rows content, list of lists
        """
        # convert data rows into a File-like object
        sio = StringIO()
        strs = []
        for row in rows:
            values = [unicode(v) for v in row]
            row_str = "\t".join(values)
            strs.append(row_str)
        sio.write('\n'.join(strs))
        sio.seek(0)

        # call postgres COPY command to write data into database in bulk
        # this is the most effective way
        cur.copy_from(sio, table, columns=columns)
        conn.commit()

    def update_database(self):
        """
        Update metadata database content.
        """
        # connect to database
        db_url_str = os.getenv("DATABASE_URL")
        assert db_url_str is not None
        parse_res = urlparse.urlparse(db_url_str)
        conn = psycopg2.connect("dbname='%s' user='%s' password='%s' host='%s'" %
                                (parse_res.path[1:], parse_res.username, parse_res.password, parse_res.hostname))
        cur = conn.cursor()

        # clear out existing tables
        # NOTE: this will cause a 1-2 minute downtime for the usage
        cur.execute("truncate table %s" % ",".join(
            ["mddb_entity", "mddb_entity_type", "mddb_entity_attribute", "mddb_entity_relationship"]))
        conn.commit()

        # copy data into tables
        # NOTE: the mddb_entity_type must be first filled due to forein-key constraints on the other tables
        self._copy_into_table(conn, cur, "mddb_entity_type", ["id", "type"], self.rows_types)
        self._copy_into_table(conn, cur, "mddb_entity", ["id", "type_id", "code", "name"], self.rows_entities)
        self._copy_into_table(conn, cur, "mddb_entity_attribute", ["id", "metadata_id", "key", "value"],
                              self.rows_attributes)
        self._copy_into_table(conn, cur, "mddb_entity_relationship", ["from_id", "to_id"], self.rows_relationships)

        # close database connection
        cur.close()
        conn.close()

    def _get_asn_info(self):
        """
        Get ASN info using PANDA API
        """

        logging.info("retrieving ASN info from PANDA API")
        cur_page = 1
        perpage = 4000
        has_next = True
        while has_next:
            try:
                response = requests.get(
                    'https://api.panda.caida.org/as2org/v1/asns/?page=%d&perpage=%d' % (cur_page, perpage)
                )
                response.raise_for_status()
                res = response.json()
                has_next = res['pageInfo']['hasNextPage'] and len(res['data']) == perpage
                for info in res['data']:
                    asn = info['asn']
                    asn_name = info['asnName']
                    org_name = info['orgName']
                    self.ASN_INFO[asn] = (asn_name, org_name)
                cur_page += 1
            except requests.HTTPError as http_err:
                print('HTTP error occurred')
                raise http_err
            except Exception as err:
                print('Other error occurred')
                raise err

    def getid(self, fqid, must_exist=False):
        if fqid in self.FQID_TO_ID:
            return self.FQID_TO_ID[fqid]
        if must_exist:
            return None
        entity_id = self.NEXT_ID
        self.NEXT_ID += 1
        self.FQID_TO_ID[fqid] = entity_id
        return entity_id

    def log_entity(self, id, type, code, name, attrs):
        """
        This function saves an entity and its corresponding type and attributes for later to write to databse.

        :param id: id of the entity
        :param type: type of the entity
        :param code: short name or code of the entity
        :param name: full name of the entity
        :param attrs: attribute key/value pairs
        """

        id = int(id)  # make sure id is an integer

        # decode potentially unicode name from ascii to unicode (Python2-only step)
        try:
            name = name.decode("utf-8")
        except UnicodeDecodeError as err:
            print code, name
            raise err

        try:
            if type not in self.types:
                # save new type
                self.types[type] = self.next_type_id
                self.rows_types.append((self.next_type_id, type))
                self.next_type_id += 1
            type_id = self.types[type]

            # save entity
            self.rows_entities.append((id, type_id, code, name))

            # save attributes of the entity
            for key, val in list(attrs.items()):
                self.rows_attributes.append((self.next_attr_id, id, key, val))
                self.next_attr_id += 1
        except ValueError as err:
            print self.next_type_id, id, type, code, name, attrs
            raise err

    def _generate_continents(self):
        """
        Generate continent entities
        """
        logging.info("Generating continent entities")
        # first, dump the continents
        continents = [
            {
                'code': '??',
                'name': '[Unknown Continent]',
            },
            {
                'code': 'AF',
                'name': 'Africa',
            },
            {
                'code': 'AN',
                'name': 'Antarctica',
            },
            {
                'code': 'AS',
                'name': 'Asia',
            },
            {
                'code': 'EU',
                'name': 'Europe',
            },
            {
                'code': 'NA',
                'name': 'North America',
            },
            {
                'code': 'OC',
                'name': 'Oceania',
            },
            {
                'code': 'SA',
                'name': 'South America',
            },
        ]

        for continent in continents:
            fqid = GEO_PFX + '.' + continent['code']
            self.log_entity(id=self.getid(fqid),
                            type='continent',
                            code=continent['code'],
                            name=continent['name'],
                            attrs={'fqid': fqid})

        return []

    def _generate_countries(self, country_codes):
        """
        Generate country entities

        :param country_codes: country codes CSV file
        :return:
        """
        logging.info("Generating country entities")
        mappings = []

        with wandio.open(country_codes) as fh:
            csvreader = csv.reader(fh, delimiter=',', quotechar='"')
            for row in csvreader:
                (iso3, iso2, name, reg, cont_code, cont_name, code_int) = row

                # cleanup based on code from
                # https://github.com/CAIDA/libipmeta/blob/master/lib/providers/ipmeta_provider_netacq_edge.c
                # fix UK => GB, ** => ??, aa => AA

                if iso3 == 'ISO-3':
                    continue
                if iso2 == '?':
                    continue

                iso2 = iso2.replace('*', '?').replace('uk', 'gb').upper()
                name = name.title()
                cont_name = cont_name.replace('*', '?').replace('au', 'oc').upper()

                if iso2 == '??':
                    name = "[Unknown Country]"

                self.COUNTRY_NAMES[iso2] = name

                cont_fqid = '.'.join((GEO_PFX, cont_name))
                cont_id = self.getid(cont_fqid)
                fqid = '.'.join((GEO_PFX, cont_name, iso2))
                id = self.getid(fqid)
                mappings.append((cont_id, id))
                self.log_entity(id=id, type='country', code=iso2, name=name,
                                attrs={'fqid': fqid})

        return mappings

    def _generate_regions(self, region_polygons):
        """
        Generate region entities.

        :param region_polygons: region-level geolocation polygons file.
        :return:
        """
        logging.info("Generating region entities")
        mappings = []

        with wandio.open(region_polygons) as fh:
            csvreader = csv.reader(fh, delimiter=',', quotechar='"')
            for row in csvreader:
                (polyid, pfqid, name, usercode) = row

                if pfqid == 'fqid':
                    continue

                name = name.replace('?', '[Unknown Region]', 1)
                if name == "":
                    name = "[Invalid Region (%s)]" % pfqid.split('.')[2]

                fqid = '.'.join((GEO_PFX, pfqid))
                id = self.getid(fqid)
                country_fqid = '.'.join(fqid.split('.')[0:4])
                country_iso2 = fqid.split('.')[3]

                self.REGION_NAMES[polyid] = name

                country_id = self.getid(country_fqid, must_exist=True)
                if country_id is not None:
                    mappings.append((country_id, id))

                self.log_entity(
                    id=id, type='region', code=polyid, name=name,
                    attrs={
                        'fqid': fqid,
                        'country_code': country_iso2,
                        'country_name':
                            self.COUNTRY_NAMES[country_iso2]
                    }
                )

        return mappings

    def _generate_counties(self, county_polygons):
        """
        Generate county-level entities.

        :param county_polygons:
        """
        logging.info("Generating county entities")
        mappings = []

        with wandio.open(county_polygons) as fh:
            csvreader = csv.reader(fh, delimiter=',', quotechar='"')
            for row in csvreader:
                (polyid, pfqid, name, usercode) = row

                if pfqid == 'fqid':
                    continue

                name = name.replace('?', '[Unknown County]', 1)
                if name == "":
                    name = "[Invalid County (%s)]" % pfqid.split('.')[3]

                fqid = '.'.join((GEO_PFX, pfqid))
                id = self.getid(fqid)
                region_fqid = '.'.join(fqid.split('.')[0:5])
                region_code = fqid.split('.')[4]
                region_name = self.REGION_NAMES[region_code]
                country_code = fqid.split('.')[3]
                country_name = self.COUNTRY_NAMES[country_code]

                region_id = self.getid(region_fqid, must_exist=True)
                if region_id is not None:
                    mappings.append((region_id, id))

                self.log_entity(
                    id=id, type='county', code=region_code, name=name,
                    attrs={
                        'fqid': fqid,
                        'region_code': region_code,
                        'region_name': region_name,
                        'country_code': country_code,
                        'country_name': country_name
                    }
                )

        return mappings

    def _generate_ases(self, pfx2as, blocks, locations, polygon_mapping, regions, counties):
        """
        Generate AS entities.
        :param pfx2as:
        :param blocks:
        :param locations:
        :param polygon_mapping:
        :param regions:
        :param counties:
        :return:
        """
        logging.info("Generating AS entities")
        mappings = []

        self._get_asn_info()

        # process pfx2as data
        asn_prefixes = {}
        prefixes = set()
        with wandio.open(pfx2as) as fh:
            csvreader = csv.reader(fh, delimiter='\t')
            for row in csvreader:
                (prefix, length, origin) = row
                prefix = "%s/%s" % (prefix, length)
                origins = re.findall(r"\d+", origin)

                for origin in origins:
                    if origin not in asn_prefixes:
                        asn_prefixes[origin] = set()
                    prefixes.add(prefix)
                    asn_prefixes[origin].add(prefix)

        # initialize pyipmeta
        configs = [
            "-b %s" % blocks,
            "-l %s" % locations,
            "-p %s" % polygon_mapping,
            "-t %s" % regions,
            "-t %s" % counties,
        ]
        global ipm
        ipm = pyipmeta.IpMeta(provider="netacq-edge", provider_config=" ".join(configs))

        # ipmeta-lookup for all prefixes.
        # multi-threaded.
        prefix_geo = {}
        cpu_count = multiprocessing.cpu_count()
        logging.info("launching %d processes to do ipmeta lookup" % cpu_count)
        pool = multiprocessing.Pool(cpu_count)
        # actually lookup tasks are distributed here
        pfxgeo_items = pool.map(ipmeta_lookup, prefixes)
        for prefix, pfxgeo in pfxgeo_items:
            prefix_geo[prefix] = pfxgeo
        pool.terminate()
        logging.info("Processed %d results from ipmeta" % len(prefix_geo))

        # create ASN entities
        for asn in asn_prefixes:
            fqid = '.'.join(['asn', asn])
            id = self.getid(fqid)
            # default as name
            as_name = "AS%s" % asn
            if asn in self.ASN_INFO:
                as_name = "AS%s (%s)" % (asn, self.ASN_INFO[asn][0])
            attrs = {
                'fqid': fqid,
            }

            if asn in self.ASN_INFO:
                attrs['name'] = self.ASN_INFO[asn][0]
                attrs['org'] = self.ASN_INFO[asn][1]

            # how many (unique) IPs does this ASN announce
            # this is nasty, fragile, and slow code, but... meh
            rt = radix.Radix()
            for prefix in asn_prefixes[asn]:
                rt.add(prefix)
            root_prefixes = set()
            for prefix in asn_prefixes[asn]:
                if rt.search_worst(prefix).prefix == prefix:
                    root_prefixes.add(prefix)
            ip_count = 0
            for prefix in root_prefixes:
                pfxlen = int(prefix.split('/')[1])
                ip_count += (1 << (32 - pfxlen))

            attrs['ip_count'] = ip_count

            self.log_entity(id=id, type='asn', code=str(asn), name=as_name, attrs=attrs)

            # build mappings
            to_fqids = set()
            for prefix in asn_prefixes[asn]:
                if prefix in prefix_geo:
                    to_fqids.update(prefix_geo[prefix])
            for to in to_fqids:
                to_id = self.getid(to, must_exist=True)
                if to_id is not None:
                    mappings.append((id, self.getid(to)))

        return mappings

    def generate_entities(self, country_codes, region_polygons, county_polygons, pfx2as,
                          blocks, locations, polygon_mapping):
        """
        Entry point function.

        :param country_codes:
        :param region_polygons:
        :param county_polygons:
        :param pfx2as:
        :param blocks:
        :param locations:
        :param polygon_mapping:
        :return:
        """
        logging.info("Extracting Entities from: %s, %s, %s, %s" %
                     (country_codes, region_polygons, county_polygons, pfx2as))

        mappings = []

        # mappings is array of (from_id, to_id) mappings
        # but only in the forward direction.
        mappings.extend(self._generate_continents())
        mappings.extend(self._generate_countries(country_codes))
        mappings.extend(self._generate_regions(region_polygons))
        mappings.extend(self._generate_counties(county_polygons))
        mappings.extend(self._generate_ases(pfx2as, blocks, locations,
                                            polygon_mapping, region_polygons,
                                            county_polygons))

        for mapping in mappings:
            self.rows_relationships.append((mapping[0], mapping[1]))
            self.rows_relationships.append((mapping[1], mapping[0]))

        self.update_database()


def main():
    parser = argparse.ArgumentParser(description="""
    Generates entities for the Charthouse Metadata Database
    """)

    ############
    # REQUIRED #
    ############
    parser.add_argument('-p', '--pfx2as',
                        nargs='?', required=True,
                        help='CAIDA Route Views Prefix2AS File')
    # e.g. /data/routing/routeviews-prefix2as/2016/10/routeviews-rv2-20161010-1200.pfx2as.gz

    ################
    # WITH DEFAULT #
    ################
    parser.add_argument('-c', '--country-codes',
                        nargs='?', required=False,
                        help='Net Acuity country_codes.csv',
                        default="/data/external/netacuity-dumps/country_codes.csv")

    parser.add_argument('-r', '--region-polygons',
                        nargs='?', required=False,
                        help='Natrual Earth Region Polygons File',
                        default="/data/external/natural-earth/polygons/ne_10m_admin_1.regions.v3.0.0.processed.polygons.csv.gz")

    parser.add_argument('-C', '--county-polygons',
                        nargs='?', required=False,
                        help='GADM County Polygons File',
                        default="/data/external/gadm/polygons/gadm.counties.v2.0.processed.polygons.csv.gz")

    # following are needed to do pfx geolocation (and thus AS geolocation)
    parser.add_argument('-b', '--blocks',
                        nargs='?', required=False,
                        help='Net Acuity Edge Blocks File',
                        default="/data/external/netacuity-dumps/Edge-processed/netacq-4-blocks.latest.csv.gz")

    parser.add_argument('-l', '--locations',
                        nargs='?', required=False,
                        help='Net Acuity Edge Locations File',
                        default="/data/external/netacuity-dumps/Edge-processed/netacq-4-locations.latest.csv.gz")

    parser.add_argument('-P', '--polygon-mapping',
                        nargs='?', required=False,
                        help='Net Acuity Edge Polygons File',
                        default="/data/external/netacuity-dumps/Edge-processed/netacq-4-polygons.latest.csv.gz")

    opts = vars(parser.parse_args())

    #######
    # RUN #
    #######
    logging.basicConfig(level='DEBUG',
                        format='%(asctime)s|mddb-entities|%(levelname)s: %(message)s',
                        datefmt='%Y-%m-%d %H:%M:%S')
    updater = MddbUpdater()
    updater.generate_entities(**opts)


if __name__ == "__main__":
    main()
