<?php

namespace Sourceability\ConsoleToolbarBundle\Profiler;

use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class RecentProfileLoader
{
    /**
     * @var Profiler|null
     */
    private $profiler;

    public function __construct(?Profiler $profiler)
    {
        $this->profiler = $profiler;
    }

    /**
     * @return Profile[]
     */
    public function loadSince(?int $startTimestamp): array
    {
        if (null === $this->profiler) {
            return [];
        }

        if (null !== $startTimestamp) {
            $startTimestamp = (string) $startTimestamp;
        }

        $newProfiles = $this->profiler->find('', '', (string) 100, '', $startTimestamp, '');

        $profiles = [];
        foreach ($newProfiles as $newProfile) {
            $profile = $this->profiler->loadProfile($newProfile['token']);

            if (null === $profile) {
                continue;
            }

            $profiles[] = $profile;
        }

        return $profiles;
    }

    public function countSince(?int $startTimestamp): int
    {
        if (null === $this->profiler) {
            return 0;
        }

        if (null !== $startTimestamp) {
            $startTimestamp = (string) $startTimestamp;
        }

        $newProfiles = $this->profiler->find('', '', (string) 100, '', $startTimestamp, '');

        return \count($newProfiles);
    }
}
