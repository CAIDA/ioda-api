<?php
/**
 * This software is Copyright (c) 2013 The Regents of the University of
 * California. All Rights Reserved. Permission to copy, modify, and distribute this
 * software and its documentation for academic research and education purposes,
 * without fee, and without a written agreement is hereby granted, provided that
 * the above copyright notice, this paragraph and the following three paragraphs
 * appear in all copies. Permission to make use of this software for other than
 * academic research and education purposes may be obtained by contacting:
 *
 * Office of Innovation and Commercialization
 * 9500 Gilman Drive, Mail Code 0910
 * University of California
 * La Jolla, CA 92093-0910
 * (858) 534-5815
 * invent@ucsd.edu
 *
 * This software program and documentation are copyrighted by The Regents of the
 * University of California. The software program and documentation are supplied
 * "as is", without any accompanying services from The Regents. The Regents does
 * not warrant that the operation of the program will be uninterrupted or
 * error-free. The end-user understands that the program was developed for research
 * purposes and is advised not to rely exclusively on the program for any reason.
 *
 * IN NO EVENT SHALL THE UNIVERSITY OF CALIFORNIA BE LIABLE TO ANY PARTY FOR
 * DIRECT, INDIRECT, SPECIAL, INCIDENTAL, OR CONSEQUENTIAL DAMAGES, INCLUDING LOST
 * PROFITS, ARISING OUT OF THE USE OF THIS SOFTWARE AND ITS DOCUMENTATION, EVEN IF
 * THE UNIVERSITY OF CALIFORNIA HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE. THE UNIVERSITY OF CALIFORNIA SPECIFICALLY DISCLAIMS ANY WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
 * FITNESS FOR A PARTICULAR PURPOSE. THE SOFTWARE PROVIDED HEREUNDER IS ON AN "AS
 * IS" BASIS, AND THE UNIVERSITY OF CALIFORNIA HAS NO OBLIGATIONS TO PROVIDE
 * MAINTENANCE, SUPPORT, UPDATES, ENHANCEMENTS, OR MODIFICATIONS.
 */

namespace  App\TimeSeries\Backend\Graphite\Expression\Humanize\Provider;


class DirectHumanizeProvider extends AbstractHumanizeProvider
{

    private static $table = [
        'active' => 'Active Probing',
        'pitr' => 'Pinging in the Rain',
        'sample' => 'Sample Data',
        'bgp' => 'BGP',
        'routingtables' => 'Routing Tables',
        'prefix-visibility' => 'Global Prefix Visibility',
        'v4' => 'IPv4',
        'v6' => 'IPv6',
        'visibility_threshold' => 'Visibility Threshold',
        'v4_full_feed_peers_cnt' => '# IPv4 Full-Feed Peers',
        'v6_full_feed_peers_cnt' => '# IPv6 Full-Feed Peers',
        'v4_peers_cnt' => '# IPv4 Peers',
        'v6_peers_cnt' => '# IPv6 Peers',
        'ipv4_pfx_cnt' => '# IPv4 Prefixes',
        'ipv6_pfx_cnt' => '# IPv6 Prefixes',
        'min_1_ff_peer_asn' => 'At least 1 Full-Feed Peer ASN',
        'min_25%_ff_peer_asns' => 'At least 25% of Full-Feed Peer ASNs',
        'min_50%_ff_peer_asns' => 'At least 50% of Full-Feed Peer ASNs',
        'min_75%_ff_peer_asns' => 'At least 75% of Full-Feed Peer ASNs',
        'min_100%_ff_peer_asns' => 'At least 100% of Full-Feed Peer ASNs',
        'visible_asns_cnt' => '# Visible ASNs',
        'visible_prefixes_cnt' => '# Visible Prefixes',
        'visible_slash24_cnt' => '# Visible /24 blocks',
        'visible_slash64_cnt' => '# Visible /64 blocks',
        'visibile_ips_cnt' => '# Visible IPs (deprecated)',
        'ff_peer_asns_sum' => '# Full-Feed Peer ASNs (deprecated)',
        'carbon' => '[Carbon]',
        'high-precision' => 'High-Precision',
        'systems' => '[Backend Monitoring]',
        'filesystem' => 'File Systems',
        'ntp' => 'NTP',
        'os' => 'Operating System',
        'available_bytes' => 'Available Bytes',
        'used_bytes' => 'Used Bytes',
        'clock_offset_ms' => 'Clock Offset (ms)',
        'cpu' => 'CPU',
        'ctx_switches' => '# Context Switches',
        'idle_pct' => '% Idle',
        'interrupts' => '# Interrupts',
        'system_pct' => '% System',
        'user_pct' => '% User',
        'load' => 'System Load',
        'avg_1_min' => '1 Minute Avg',
        'avg_5_min' => '5 Minute Avg',
        'avg_15_min' => '15 Minute Avg',
        'active_bytes' => 'Active Bytes',
        'buf_bytes' => 'Buffered Bytes',
        'cache_bytes' => 'Cached Bytes',
        'free_bytes' => 'Free Bytes',
        'inact_bytes' => 'Inactive Bytes',
        'wired_bytes' => 'Wired Bytes',
        'largest_process_bytes' => 'Largest Process Size (bytes)',
        'running_cnt' => '# Running Processes',
        'total_cnt' => '# Processes',
        'ucsd-nt' => 'UCSD Network Telescope',
        'merit-nt' => 'Merit Network Telescope',
        'non-erratic' => 'Non-Erratic',
        'non-spoofed' => 'Non-Spoofed',
        'rfc5735-non-spoofed' => 'Unrouted, Non-Spoofed',
        'rsdos' => 'Randomly-Spoofed Denial-of-Service',
        'geo' => 'Geolocation',
        'maxmind' => 'Maxmind GeoLite',
        'netacuity' => 'Net Acuity',
        'meta' => '[Operational Data]',
        'pfx2as' => 'CAIDA Prefix To AS',
        'asn' => 'Autonomous System Number (ASN)',
        'tcp' => 'TCP',
        'udp' => 'UDP',
        'dst_port' => 'Destination Port',
        'src_port' => 'Source Port',
        'filter-criteria' => 'Filter Criteria',
        'uniq_src_ip' => '# Unique Source IPs',
        'uniq_dst_ip' => '# Unique Destination IPs',
        'pkt_cnt' => '# Packets',
        'ip_len' => '# Bytes',
        'sie' => 'SIE',
        'ioda' => 'IODA',
        'trinarkular' => 'Per-/24 Pings (Contact ioda-info@caida.org if you see this message)',
        'ping-slash24' => 'Per-/24 Pings',

        // RSDoS names
        'attack_vector_cnt' => '# Attacks',
        'attack_duration_max' => 'Max Attack Duration',
        'attack_duration_mean' => 'Mean Attack Duration',
        'attack_duration_median' => 'Median Attack Duration',
        'attack_duration_min' => 'Min Attack Duration',
        'attacked_asn_cnt' => '# Attacked ASNs',
        'attacked_country_cnt' => '# Attacked Countries',
        'attacks_per_asn_max' => '# Attacks per ASN (Max)',
        'attacks_per_asn_mean' => '# Attacks per ASN (Mean)',
        'attacks_per_asn_median' => '# Attacks per ASN (Median)',
        'attacks_per_asn_min' => '# Attacks per ASN (Min)',
        'attacks_per_country_max' => '# Attacks per Country (Max)',
        'attacks_per_country_mean' => '# Attacks per Country (Mean)',
        'attacks_per_country_median' => '# Attacks per Country (Median)',
        'attacks_per_country_min' => '# Attacks per Country (Min)',
        'attacks_per_ip_max' => '# Attacks per IP (Max)',
        'attacks_per_ip_mean' => '# Attacks per IP (Mean)',
        'attacks_per_ip_median' => '# Attacks per IP (Median)',
        'attacks_per_ip_min' => '# Attacks per IP (Min)',
        'attacks_per_slash16_max' => '# Attacks per /16 (Max)',
        'attacks_per_slash16_mean' => '# Attacks per /16 (Mean)',
        'attacks_per_slash16_median' => '# Attacks per /16 (Median)',
        'attacks_per_slash16_min' => '# Attacks per /16 (Min)',
        'attacks_per_slash24_max' => '# Attacks per /24 (Max)',
        'attacks_per_slash24_mean' => '# Attacks per /24 (Mean)',
        'attacks_per_slash24_median' => '# Attacks per /24 (Median)',
        'attacks_per_slash24_min' => '# Attacks per /24 (Min)',
        'unique_ip_cnt' => '# Attacked IPs',
        'unique_slash16_cnt' => '# Attacked /16s',
        'unique_slash24_cnt' => '# Attacked /24s',

        ];

    public function humanize(string $fqid, array &$nodes,
                             string $finalNode): ?string
    {
        if (array_key_exists($finalNode, static::$table)) {
            return static::$table[$finalNode];
        }
        return null;
    }

}
