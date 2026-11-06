<?php

namespace Tests\Unit;

use App\Models\QuestionnaireQuestion;
use App\Support\LegacyQuestionnaireAnswers;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LegacyQuestionnaireAnswersTest extends TestCase
{
    public function test_it_decodes_legacy_answers_and_maps_numeric_options(): void
    {
        $service = new LegacyQuestionnaireAnswers;
        $decoded = $service->decode(serialize(['G1' => '52']));
        $question = (new QuestionnaireQuestion)->forceFill(['type' => 'radio']);

        $this->assertSame('Συμφωνώ απόλυτα', $service->normalize($question, $decoded['G1']));
    }

    public function test_it_converts_legacy_html_to_plain_text(): void
    {
        $service = new LegacyQuestionnaireAnswers;
        $question = (new QuestionnaireQuestion)->forceFill(['type' => 'textarea']);

        $this->assertSame("Πρώτη 'παράγραφος' & κείμενο.\nΔεύτερη / γραμμή.", $service->normalize(
            $question,
            '<div>Πρώτη &#039;παράγραφος&#039; &amp; κείμενο.</div><div>Δεύτερη / γραμμή.</div>',
        ));
    }

    public function test_it_repairs_incorrect_legacy_string_lengths(): void
    {
        $serialized = serialize(['comm' => 'Ελληνικό κείμενο', 'A1' => '6']);
        $broken = preg_replace('/s:\d+:"Ελληνικό κείμενο"/', 's:1:"Ελληνικό κείμενο"', $serialized);

        $this->assertSame(
            ['comm' => 'Ελληνικό κείμενο', 'A1' => '6'],
            (new LegacyQuestionnaireAnswers)->decode($broken),
        );
    }

    public function test_it_rejects_invalid_serialized_data(): void
    {
        $this->expectException(RuntimeException::class);

        (new LegacyQuestionnaireAnswers)->decode('not serialized');
    }
}
