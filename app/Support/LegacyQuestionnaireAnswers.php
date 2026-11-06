<?php

namespace App\Support;

use App\Models\ProviderQuestionnaireQuestion;
use App\Models\QuestionnaireQuestion;
use RuntimeException;

class LegacyQuestionnaireAnswers
{
    private const LABELS = [
        1 => 'ΠΑΡΑ ΠΟΛΥ', 2 => 'ΠΟΛΥ', 3 => 'ΑΡΚΕΤΑ', 4 => 'ΛΙΓΟ', 5 => 'ΚΑΘΟΛΟΥ',
        6 => 'ΝΑΙ', 7 => 'ΟΧΙ',
        8 => 'α. στη βελτίωση των γνώσεων του προσωπικού σας',
        9 => 'β. στην ανταλλαγή τεχνογνωσίας και καλών πρακτικών',
        10 => 'γ. στην εδραίωση μιας μελλοντικής συνεργασίας με τον/τη φοιτητή/τρια πέραν της Πρακτικής',
        11 => 'ΑΡΙΣΤΟΣ/Η', 12 => 'ΠΟΛΥ ΚΑΛΟΣ/Η', 13 => 'ΚΑΛΟΣ/Η', 14 => 'ΜΕΤΡΙΟΣ/Α', 15 => 'ΑΝΕΠΑΡΚΗΣ/Η',
        16 => 'ΔΗΜΟΣΙΟΣ', 17 => 'ΙΔΙΩΤΙΚΟΣ', 18 => 'ΠΟΛΥ ΚΑΛΕΣ', 19 => 'ΚΑΛΕΣ', 20 => 'ΜΕΤΡΙΕΣ',
        21 => 'ΛΙΓΕΣ', 22 => 'ΕΛΑΧΙΣΤΕΣ', 23 => 'ΠΟΛΥ ΚΑΛΗ', 24 => 'ΚΑΛΗ', 25 => 'ΜΕΤΡΙΑ',
        26 => 'ΛΙΓΗ', 27 => 'ΕΛΑΧΙΣΤΗ', 28 => 'ΠΟΛΥ ΜΕΓΑΛΟ', 29 => 'ΜΕΓΑΛΟ', 30 => 'ΜΕΤΡΙΟ',
        31 => 'ΜΙΚΡΟ', 32 => 'ΠΟΛΥ ΜΙΚΡΟ', 33 => 'ΚΑΚΗ', 34 => 'ΠΟΛΥ ΚΑΚΗ', 35 => 'ΠΟΛΥ ΜΕΓΑΛΗ',
        36 => 'ΜΕΓΑΛΗ', 37 => 'ΜΙΚΡΗ', 38 => 'ΠΟΛΥ ΜΙΚΡΗ', 39 => 'ΠΑΡΑ ΠΟΛΛΕΣ', 40 => 'ΠΑΡΑ ΠΟΛΛΗ',
        41 => 'ΠΟΛΛΗ', 42 => 'ΠΟΛΛΕΣ', 43 => '1', 44 => '2', 45 => '3', 46 => '4', 47 => '5',
        48 => 'Διαφωνώ απόλυτα', 49 => 'Διαφωνώ', 50 => 'Ούτε διαφωνώ-ούτε συμφωνώ',
        51 => 'Συμφωνώ', 52 => 'Συμφωνώ απόλυτα',
    ];

    public function decode(string $serialized): array
    {
        $answers = $this->unserialize($serialized);

        if (! is_array($answers)) {
            $repaired = preg_replace_callback(
                '/s:\d+:"(.*?)";(?=s:\d+:|i:\d+;|N;|})/s',
                fn (array $match): string => 's:'.strlen($match[1]).':"'.$match[1].'";',
                $serialized,
            );
            $answers = $this->unserialize($repaired ?? $serialized);
        }

        if (! is_array($answers)) {
            throw new RuntimeException('Invalid serialized questionnaire answers.');
        }

        return $answers;
    }

    private function unserialize(string $serialized): mixed
    {
        set_error_handler(static fn (): bool => true);

        try {
            return unserialize($serialized, ['allowed_classes' => false]);
        } finally {
            restore_error_handler();
        }
    }

    public function normalize(QuestionnaireQuestion|ProviderQuestionnaireQuestion $question, mixed $answer): ?string
    {
        if ($answer === null) {
            return null;
        }

        if ($question->type === 'textarea') {
            return $this->plainText((string) $answer);
        }

        $answer = trim((string) $answer);

        if (ctype_digit($answer) && isset(self::LABELS[(int) $answer])) {
            return self::LABELS[(int) $answer];
        }

        return $answer;
    }

    private function plainText(string $value): string
    {
        $value = preg_replace('/<br\s*\/?>/i', "\n", $value) ?? $value;
        $value = preg_replace('/<\/(div|p)>/i', "\n", $value) ?? $value;
        $value = strip_tags($value);
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace("/[ \t]+\n/", "\n", $value) ?? $value;
        $value = preg_replace("/\n{3,}/", "\n\n", $value) ?? $value;

        return trim($value);
    }
}
