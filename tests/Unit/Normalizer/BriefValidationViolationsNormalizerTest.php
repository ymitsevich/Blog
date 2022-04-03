<?php

namespace App\Tests\Unit\Normalizer;

use App\Normalizer\BriefValidationViolationsNormalizer;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class BriefValidationViolationsNormalizerTest extends TestCase
{
    private BriefValidationViolationsNormalizer $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BriefValidationViolationsNormalizer();
    }

    public function testSupportsNormalization_support_true()
    {
        $object = new ConstraintViolationList();
        $assertingResult = $this->service->supportsNormalization($object);
        $this->assertEquals(true, $assertingResult);
    }

    public function testSupportsNormalization_wrong_false()
    {
        $object = new stdClass();
        $assertingResult = $this->service->supportsNormalization($object);
        $this->assertEquals(false, $assertingResult);
    }

    public function testHasCacheableSupportsMethod()
    {
        $assertingResult = $this->service->hasCacheableSupportsMethod();
        $this->assertEquals(true, $assertingResult);
    }

    public function testNormalize()
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                'wrong field:',
                null,
                [],
                null,
                'content',
                null,
            ),
        ]);

        $assertingResult = $this->service->normalize($violations);
        $this->assertEquals('wrong field: [content]', $assertingResult);
    }
}
