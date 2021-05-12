<?php

namespace Sourceability\ConsoleToolbarBundle\Profiler;

use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\HttpKernel\Profiler\ProfilerStorageInterface;
use function count;

class RecentProfileLoader
{
    private ?Profiler $profiler;
    private ?ProfilerStorageInterface $profilerStorage;

    public function __construct(?Profiler $profiler, ?ProfilerStorageInterface $profilerStorage)
    {
        $this->profiler = $profiler;
        $this->profilerStorage = $profilerStorage;
    }

    /**
     * @param int|null $startTimestamp
     *
     * @return Profile[]
     */
    public function loadSince(?int $startTimestamp): array
    {
        if (null === $this->profiler
            || null === $this->profilerStorage
        ) {
            return [];
        }

        $newProfiles = $this->profilerStorage->find('', '', 100, '', $startTimestamp);

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
        if (null === $this->profiler
            || null === $this->profilerStorage
        ) {
            return 0;
        }

        $newProfiles = $this->profilerStorage->find('', '', 100, '', $startTimestamp);

        return count($newProfiles);
    }
}
