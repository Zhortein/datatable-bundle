<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export\Job;

use Zhortein\DatatableBundle\Enum\ExportJobStatus;

final readonly class ExportJob
{
    public function __construct(
        private ExportJobIdentifier $identifier,
        private ExportJobRequest $request,
        private string $ownerIdentifier,
        private ?string $idempotencyKey,
        private ExportJobStatus $status,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
        private \DateTimeImmutable $expiresAt,
        private int $attempts = 0,
        private ?ExportJobResultMetadata $result = null,
        private ?string $failureCode = null,
    ) {
        if ('' === trim($this->ownerIdentifier)) {
            throw new \InvalidArgumentException('The export job owner identifier cannot be empty.');
        }

        if (null !== $this->idempotencyKey && '' === trim($this->idempotencyKey)) {
            throw new \InvalidArgumentException('The export job idempotency key cannot be empty when provided.');
        }

        if ($this->attempts < 0) {
            throw new \InvalidArgumentException('The export job attempt count cannot be negative.');
        }

        if ($this->expiresAt <= $this->createdAt) {
            throw new \InvalidArgumentException('The export job expiration must be later than its creation time.');
        }

        if (ExportJobStatus::Completed === $this->status && null === $this->result) {
            throw new \InvalidArgumentException('A completed export job requires result metadata.');
        }

        if (ExportJobStatus::Failed === $this->status && null === $this->failureCode) {
            throw new \InvalidArgumentException('A failed export job requires a failure code.');
        }
    }

    public static function pending(
        ExportJobIdentifier $identifier,
        ExportJobRequest $request,
        string $ownerIdentifier,
        ?string $idempotencyKey,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $expiresAt,
    ): self {
        return new self(
            identifier: $identifier,
            request: $request,
            ownerIdentifier: trim($ownerIdentifier),
            idempotencyKey: null === $idempotencyKey ? null : trim($idempotencyKey),
            status: ExportJobStatus::Pending,
            createdAt: $createdAt,
            updatedAt: $createdAt,
            expiresAt: $expiresAt,
        );
    }

    public function start(\DateTimeImmutable $now): self
    {
        if (ExportJobStatus::Pending !== $this->status) {
            throw new \LogicException(sprintf('Only a pending export job can start; current status is "%s".', $this->status->value));
        }

        return $this->with(status: ExportJobStatus::Running, now: $now, attempts: $this->attempts + 1);
    }

    public function retry(\DateTimeImmutable $now): self
    {
        if (ExportJobStatus::Running !== $this->status) {
            throw new \LogicException(sprintf('Only a running export job can be retried; current status is "%s".', $this->status->value));
        }

        return $this->with(status: ExportJobStatus::Pending, now: $now);
    }

    public function complete(
        ExportJobResultMetadata $result,
        \DateTimeImmutable $now,
        \DateTimeImmutable $expiresAt,
    ): self {
        if (ExportJobStatus::Running !== $this->status) {
            throw new \LogicException(sprintf('Only a running export job can complete; current status is "%s".', $this->status->value));
        }

        return new self(
            identifier: $this->identifier,
            request: $this->request,
            ownerIdentifier: $this->ownerIdentifier,
            idempotencyKey: $this->idempotencyKey,
            status: ExportJobStatus::Completed,
            createdAt: $this->createdAt,
            updatedAt: $now,
            expiresAt: $expiresAt,
            attempts: $this->attempts,
            result: $result,
        );
    }

    public function fail(string $failureCode, \DateTimeImmutable $now): self
    {
        if (ExportJobStatus::Running !== $this->status) {
            throw new \LogicException(sprintf('Only a running export job can fail; current status is "%s".', $this->status->value));
        }

        $failureCode = trim($failureCode);

        if ('' === $failureCode) {
            throw new \InvalidArgumentException('The export job failure code cannot be empty.');
        }

        return $this->with(
            status: ExportJobStatus::Failed,
            now: $now,
            failureCode: $failureCode,
        );
    }

    public function expire(\DateTimeImmutable $now): self
    {
        if (ExportJobStatus::Expired === $this->status) {
            return $this;
        }

        return new self(
            identifier: $this->identifier,
            request: $this->request,
            ownerIdentifier: $this->ownerIdentifier,
            idempotencyKey: $this->idempotencyKey,
            status: ExportJobStatus::Expired,
            createdAt: $this->createdAt,
            updatedAt: $now,
            expiresAt: $this->expiresAt,
            attempts: $this->attempts,
        );
    }

    public function isExpiredAt(\DateTimeImmutable $now): bool
    {
        return $now >= $this->expiresAt;
    }

    public function belongsTo(string $ownerIdentifier): bool
    {
        return hash_equals($this->ownerIdentifier, $ownerIdentifier);
    }

    public function getIdentifier(): ExportJobIdentifier
    {
        return $this->identifier;
    }

    public function getRequest(): ExportJobRequest
    {
        return $this->request;
    }

    public function getOwnerIdentifier(): string
    {
        return $this->ownerIdentifier;
    }

    public function getIdempotencyKey(): ?string
    {
        return $this->idempotencyKey;
    }

    public function getStatus(): ExportJobStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function getResult(): ?ExportJobResultMetadata
    {
        return $this->result;
    }

    public function getFailureCode(): ?string
    {
        return $this->failureCode;
    }

    private function with(
        ExportJobStatus $status,
        \DateTimeImmutable $now,
        ?int $attempts = null,
        ?string $failureCode = null,
    ): self {
        return new self(
            identifier: $this->identifier,
            request: $this->request,
            ownerIdentifier: $this->ownerIdentifier,
            idempotencyKey: $this->idempotencyKey,
            status: $status,
            createdAt: $this->createdAt,
            updatedAt: $now,
            expiresAt: $this->expiresAt,
            attempts: $attempts ?? $this->attempts,
            result: $this->result,
            failureCode: $failureCode,
        );
    }
}
