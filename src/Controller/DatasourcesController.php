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

namespace App\Controller;

use App\Response\Envelope;
use App\Service\DatasourceService;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class DatasourcesController
 * @package App\Controller
 * @Route("/datasources", name="datasources_")
 */
class DatasourcesController extends ApiController
{
    /**
     * Get datasources
     *
     * @Route("/", methods={"GET"}, name="getall")
     * @Route("/{datasource}", methods={"GET"}, name="findone")
     * @SWG\Tag(name="Data Sources")
     * @SWG\Response(
     *     response=200,
     *     description="Return data source matched by the lookup term",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"datasources.lookup"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     ref=@Model(type=\App\Entity\Ioda\DatasourceEntity::class, groups={"public"})
     *                 )
     *             )
     *         }
     *     )
     * )
     *
     * @var string|null datasource
     * @var Request $request
     * @var SerializerInterface $serializer
     * @return JsonResponse
     */
    public function datasourceLookup(?string $datasource, Request $request, DatasourceService $datasourceService)
    {
        $env = new Envelope('datasources',
            'query',
            [],
            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }

        // $datasource is null, return all datasources
        if(!isset($datasource)){
            $env->setData($datasourceService->getAllDatasources());
            return $this->json($env);
        }

        // $datasource is not null, return only the matching datasource
        try {
            $env->setData($datasourceService->getDatasource($datasource));
        } catch (\InvalidArgumentException $ex) {
            $env->setError($ex->getMessage());
            return $this->json($env, 400);
        }
        return $this->json($env);
    }
}
