<?php

namespace App\Repository;

use App\Entity\Ioda\SymUrl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method SymUrl|null find($id, $lockMode = null, $lockVersion = null)
 * @method SymUrl|null findOneBy(array $criteria, array $orderBy = null)
 * @method SymUrl[]    findAll()
 * @method SymUrl[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SymUrlRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, SymUrl::class);
    }

    /**
     * @param string $shortTag
     * @return SymUrl|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByShortTag(string $shortTag): ?SymUrl
    {
        return $this->createQueryBuilder('s')
                    ->andWhere('s.shortTag = :val')
                    ->setParameter('val', $shortTag)
                    ->getQuery()
                    ->getOneOrNullResult();
    }

    /**
     * @param string $longUrl
     * @return SymUrl[]|null
     */
    public function findByLongUrl(string $longUrl): ?array
    {
        return $this->createQueryBuilder('s')
                    ->andWhere('s.longUrl = :val')
                    ->setParameter('val', $longUrl)
                    ->getQuery()
                    ->getResult();
    }

    public function updateStats(SymUrl $symUrl)
    {
        $symUrl->setDateLastUsed(new \DateTime());
        $symUrl->setUseCount($symUrl->getUseCount() + 1);
        $em = $this->getEntityManager();
        $em->persist($symUrl);
        $em->flush();
    }

    /**
     * @param string $longUrl
     * @param null|string $shortTag
     * @return SymUrl
     * @throws \Doctrine\ORM\ORMException
     */
    public function newSymUrl(string $longUrl, ?string $shortTag): SymUrl
    {
        $symurl = new SymUrl();
        $symurl->setLongUrl($longUrl);
        if (!$shortTag) {
            /* create new short url */
            $shortTag = $this->shorten($longUrl);
        }
        $symurl->setShortTag($shortTag);
        $em = $this->getEntityManager();
        $em->persist($symurl);
        $em->flush();
        return $symurl;
    }

    /**
     * @param string $longUrl
     * @return string
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function shorten(string $longUrl): string
    {
        $length = 1;
        $shortTag = null;
        $hash = $this->hashLong($longUrl);
        $hashLen = strlen($hash);
        while (true) {
            if ($length > $hashLen) {
                $hash .= $hash;
                $hashLen = strlen($hash);
            }
            $shortTag = substr($hash, 0, $length);
            if ($this->findOneByShortTag($shortTag) == null) {
                // this is a new short, we can keep it
                break;
            }
            $length++;
        }
        return $shortTag;
    }

    private function hashLong(string $longUrl): string
    {
        $md5 = md5($longUrl);
        $hash = pack('H*', $md5);
        $hash = base64_encode($hash);
        return str_replace(array('+', '/', '='), array('', '', ''), $hash);
    }
}
