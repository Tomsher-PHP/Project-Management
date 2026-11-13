<?php

namespace App\Services\DataSync;

use App\Models\Customer;
use App\Models\DataSyncEntityMapping;
use App\Models\Project;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ExternalEntityMappingService
{
    /**
     * Find an existing mapping for an external code and/or external id.
     *
     * @param  int  $apiClientId
     * @param  string  $entityType
     * @param  string|null  $externalCode
     * @param  string|null  $externalId
     * @return DataSyncEntityMapping|null
     *
     * @throws ConflictHttpException
     */
    public function findMapping(
        int $apiClientId,
        string $entityType,
        ?string $externalCode,
        ?string $externalId
    ): ?DataSyncEntityMapping {
        $cleanCode = $this->sanitizeIdentifier($externalCode);
        $cleanId = $this->sanitizeIdentifier($externalId);

        if ($cleanCode === null && $cleanId === null) {
            return null;
        }

        $mappingByCode = null;
        if ($cleanCode !== null) {
            $mappingByCode = DataSyncEntityMapping::query()
                ->where('api_client_id', $apiClientId)
                ->where('entity_type', $entityType)
                ->where('external_code', $cleanCode)
                ->first();
        }

        $mappingById = null;
        if ($cleanId !== null) {
            $mappingById = DataSyncEntityMapping::query()
                ->where('api_client_id', $apiClientId)
                ->where('entity_type', $entityType)
                ->where('external_id', $cleanId)
                ->first();
        }

        if ($mappingByCode !== null && $mappingById !== null) {
            if ((int) $mappingByCode->local_id !== (int) $mappingById->local_id) {
                throw new ConflictHttpException("Conflicting external {$entityType} identifiers.");
            }

            return $mappingByCode;
        }

        if ($mappingByCode !== null) {
            if ($cleanId !== null && $mappingByCode->external_id !== null && $mappingByCode->external_id !== $cleanId) {
                throw new ConflictHttpException("Conflicting external {$entityType} identifiers.");
            }

            return $mappingByCode;
        }

        if ($mappingById !== null) {
            if ($cleanCode !== null && $mappingById->external_code !== null && $mappingById->external_code !== $cleanCode) {
                throw new ConflictHttpException("Conflicting external {$entityType} identifiers.");
            }

            return $mappingById;
        }

        return null;
    }

    /**
     * Resolve a customer mapping for the authenticated client.
     */
    public function resolveCustomerMapping(
        int $apiClientId,
        ?string $externalCode,
        ?string $externalId
    ): ?DataSyncEntityMapping {
        return $this->findMapping($apiClientId, DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER, $externalCode, $externalId);
    }

    /**
     * Resolve a project mapping for the authenticated client.
     */
    public function resolveProjectMapping(
        int $apiClientId,
        ?string $externalCode,
        ?string $externalId
    ): ?DataSyncEntityMapping {
        return $this->findMapping($apiClientId, DataSyncEntityMapping::ENTITY_TYPE_PROJECT, $externalCode, $externalId);
    }

    /**
     * Validate that a mapped customer matches the supplied PMS customer code.
     *
     * @throws ConflictHttpException
     */
    public function validateCustomerCode(Customer $customer, ?string $pmsCustomerCode): void
    {
        $cleanPmsCode = $this->sanitizeIdentifier($pmsCustomerCode);
        if ($cleanPmsCode !== null && $customer->customer_code !== $cleanPmsCode) {
            throw new ConflictHttpException('Conflicting PMS customer code.');
        }
    }

    /**
     * Validate that a mapped project belongs to the expected customer.
     *
     * @throws ConflictHttpException
     */
    public function validateProjectCustomer(Project $project, int $resolvedCustomerId): void
    {
        if ((int) $project->customer_id !== $resolvedCustomerId) {
            throw new ConflictHttpException('Mapped project belongs to a different customer.');
        }
    }

    /**
     * Create a permanent external entity mapping.
     *
     * @param  int  $apiClientId
     * @param  string  $entityType
     * @param  string|null  $externalCode
     * @param  string|null  $externalId
     * @param  int  $localId
     * @return DataSyncEntityMapping
     *
     * @throws InvalidArgumentException
     * @throws ConflictHttpException
     */
    public function createMapping(
        int $apiClientId,
        string $entityType,
        ?string $externalCode,
        ?string $externalId,
        int $localId
    ): DataSyncEntityMapping {
        $cleanCode = $this->sanitizeIdentifier($externalCode);
        $cleanId = $this->sanitizeIdentifier($externalId);

        if ($cleanCode === null && $cleanId === null) {
            throw new InvalidArgumentException("At least one external identifier must be provided for {$entityType} mapping.");
        }

        try {
            return DataSyncEntityMapping::create([
                'api_client_id' => $apiClientId,
                'entity_type' => $entityType,
                'external_code' => $cleanCode,
                'external_id' => $cleanId,
                'local_id' => $localId,
            ]);
        } catch (UniqueConstraintViolationException|QueryException $e) {
            throw new ConflictHttpException("Duplicate external identifier mapping for {$entityType}.");
        }
    }

    /**
     * Safely enrich an existing mapping with a missing secondary identifier.
     *
     * @throws ConflictHttpException
     */
    public function enrichMapping(
        DataSyncEntityMapping $mapping,
        ?string $externalCode,
        ?string $externalId
    ): DataSyncEntityMapping {
        $cleanCode = $this->sanitizeIdentifier($externalCode);
        $cleanId = $this->sanitizeIdentifier($externalId);

        $changed = false;

        if ($mapping->external_code === null && $cleanCode !== null) {
            $existing = DataSyncEntityMapping::query()
                ->where('api_client_id', $mapping->api_client_id)
                ->where('entity_type', $mapping->entity_type)
                ->where('external_code', $cleanCode)
                ->where('id', '!=', $mapping->id)
                ->first();

            if ($existing) {
                throw new ConflictHttpException("Conflicting external {$mapping->entity_type} code.");
            }

            $mapping->external_code = $cleanCode;
            $changed = true;
        }

        if ($mapping->external_id === null && $cleanId !== null) {
            $existing = DataSyncEntityMapping::query()
                ->where('api_client_id', $mapping->api_client_id)
                ->where('entity_type', $mapping->entity_type)
                ->where('external_id', $cleanId)
                ->where('id', '!=', $mapping->id)
                ->first();

            if ($existing) {
                throw new ConflictHttpException("Conflicting external {$mapping->entity_type} id.");
            }

            $mapping->external_id = $cleanId;
            $changed = true;
        }

        if ($changed) {
            try {
                $mapping->save();
            } catch (UniqueConstraintViolationException|QueryException $e) {
                throw new ConflictHttpException("Duplicate external identifier mapping for {$mapping->entity_type}.");
            }
        }

        return $mapping;
    }

    /**
     * Sanitize string identifier to null if empty.
     */
    public function sanitizeIdentifier(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}

