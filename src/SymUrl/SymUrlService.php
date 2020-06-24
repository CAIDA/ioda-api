<?php


namespace App\SymUrl;


use App\Entity\Ioda\SymUrl;
use App\Repository\SymUrlRepository;

class SymUrlService
{
    /**
     * @var SymUrlRepository
     */
    private $symRepo;

    public function __construct(SymUrlRepository $symRepo)
    {
        $this->symRepo = $symRepo;
    }

    /**
     * @param string $url
     * @param null|string $shortTag
     * @return SymUrl
     * @throws \Doctrine\ORM\ORMException
     */
    public function createOrGet(string $url, ?string $shortTag): SymUrl
    {
        // first, if short is not null, then we need to look in the db in case
        // it already exists
        if ($shortTag) {
            if (($sym = $this->symRepo->findOneByShortTag($shortTag))) {
                // we have a record with this name,
                // lets see if it is the one they wanted
                if ($sym->getLongUrl() == $url) {
                    return $sym;
                } else {
                    // there is already a url with this short tag and it points
                    // to somewhere else.
                    //  well, too bad, you're getting a short tag that i pick
                    $shortTag = null;
                }
            }
        }

        // lets see if we can find a matching long record
        if (!$shortTag && ($symurls = $this->symRepo->findByLongUrl($url))) {
            // we do not have an explicit short name, so happy to take any
            // result that we can find
            // there may be multiple matches for this long if someone
            // created with a manual short url. we simply return the first
            return $symurls[0];
        }

        // when we get here, we know that there is not an existing short
        // with the same name, so it is safe to create a new symurl
        return $this->symRepo->newSymUrl($url, $shortTag);
    }

    /**
     * @param string $shortTag
     * @param bool $updateStats
     * @return SymUrl|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getExisting(string $shortTag,
                                bool $updateStats = false): ?SymUrl
    {
        $sym = $this->symRepo->findOneByShortTag($shortTag);
        if ($updateStats && $sym) {
            $this->symRepo->updateStats($sym);
        }
        return $sym;
    }
}
