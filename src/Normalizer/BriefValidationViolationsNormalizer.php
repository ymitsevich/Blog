<?php

namespace App\Normalizer;

use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class BriefValidationViolationsNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public function normalize(mixed $object, string $format = null, array $context = []): string
    {
        /** @var ConstraintViolationList $object */
        $dataArray = [];
        foreach ($object as $violation) {
            $dataArray[] = $violation->getMessage() . " [{$violation->getPropertyPath()}]";
        }

        return implode(PHP_EOL, $dataArray);
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof ConstraintViolationListInterface;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return __CLASS__ === static::class;
    }
}
