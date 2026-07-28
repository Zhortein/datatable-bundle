<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Preference;

use Psr\Cache\CacheItemPoolInterface;
use Zhortein\DatatableBundle\Exception\DatatablePreferenceStorageException;

final readonly class CacheDatatablePreferenceProvider implements WritableDatatablePreferenceProviderInterface
{
    private const int PAYLOAD_VERSION = 1;
    private const string KEY_PREFIX = 'zhortein_datatable_preference_';

    public function __construct(
        private CacheItemPoolInterface $cachePool,
        private int $ttl = 31536000,
    ) {
        if ($this->ttl < 1) {
            throw new \InvalidArgumentException('The datatable preference cache TTL must be greater than or equal to 1.');
        }
    }

    public function getPreference(string $datatableName): DatatablePreference
    {
        return DatatablePreference::empty();
    }

    public function getPreferenceForScope(DatatablePreferenceScope $scope): DatatablePreference
    {
        try {
            $item = $this->cachePool->getItem($this->createKey($scope));

            if (!$item->isHit()) {
                return DatatablePreference::empty();
            }

            $payload = $item->get();

            if (
                !is_array($payload)
                || self::PAYLOAD_VERSION !== ($payload['version'] ?? null)
                || $scope->getSchemaVersion() !== ($payload['schemaVersion'] ?? null)
                || !is_array($payload['preference'] ?? null)
            ) {
                $this->cachePool->deleteItem($this->createKey($scope));

                return DatatablePreference::empty();
            }

            /** @var array<string, mixed> $preference */
            $preference = $payload['preference'];

            return DatatablePreference::fromStorageArray($preference);
        } catch (\InvalidArgumentException) {
            $this->deleteQuietly($scope);

            return DatatablePreference::empty();
        } catch (\Throwable) {
            return DatatablePreference::empty();
        }
    }

    public function savePreference(
        DatatablePreferenceScope $scope,
        DatatablePreference $preference,
    ): void {
        try {
            $item = $this->cachePool->getItem($this->createKey($scope));
            $item->set([
                'version' => self::PAYLOAD_VERSION,
                'schemaVersion' => $scope->getSchemaVersion(),
                'preference' => $preference->toStorageArray(),
            ]);
            $item->expiresAfter($this->ttl);

            if (!$this->cachePool->save($item)) {
                throw new DatatablePreferenceStorageException('The datatable preference could not be saved.');
            }
        } catch (DatatablePreferenceStorageException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new DatatablePreferenceStorageException('The datatable preference could not be saved.', previous: $exception);
        }
    }

    public function resetPreference(DatatablePreferenceScope $scope): void
    {
        try {
            if (!$this->cachePool->deleteItem($this->createKey($scope))) {
                throw new DatatablePreferenceStorageException('The datatable preference could not be reset.');
            }
        } catch (DatatablePreferenceStorageException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new DatatablePreferenceStorageException('The datatable preference could not be reset.', previous: $exception);
        }
    }

    private function createKey(DatatablePreferenceScope $scope): string
    {
        return self::KEY_PREFIX.$scope->getStorageKey();
    }

    private function deleteQuietly(DatatablePreferenceScope $scope): void
    {
        try {
            $this->cachePool->deleteItem($this->createKey($scope));
        } catch (\Throwable) {
        }
    }
}
