<?php

namespace App\Normalizer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;

class ReferenceEntityDenormalizer implements ContextAwareDenormalizerInterface
{
    public const REFERENCE_ENTITIES_BY_ID = 'reference_entities_by_id';

    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function denormalize($data, string $type, string $format = null, array $context = []): mixed
    {
        $id = (int) $data['id'];

        return $this->em->getReference($type, $id);
    }

    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return is_array($data) &&
            array_key_exists('id', $data) &&
            count($data) === 1 &&
            $this->enabled($context);
    }

    private function enabled(array $context): bool
    {
        return array_key_exists(self::REFERENCE_ENTITIES_BY_ID, $context) &&
            $context[self::REFERENCE_ENTITIES_BY_ID];
    }
}
