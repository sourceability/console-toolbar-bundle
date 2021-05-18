<?php

namespace Sourceability\ConsoleToolbarBundle\Profiler;

use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\HttpKernel\Profiler\ProfilerStorageInterface;
use function count;

class RecentProfileLoader
{
    private ?Profiler $profiler;

    public function __construct(?Profiler $profiler)
    {
        $this->profiler = $profiler;
    }

    /**
     * @param int|null $startTimestamp
     *
     * @return Profile[]
     */
    public function loadSince(?int $startTimestamp): array
    {
        if (null === $this->profiler) {
            return [];
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

        $newProfiles = $this->profiler->find('', '', (string) 100, '', $startTimestamp, '');

        return count($newProfiles);
    }
}
