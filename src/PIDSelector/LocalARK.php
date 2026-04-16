<?php
namespace PersistentIdentifiers\PIDSelector;

use Omeka\Settings\Settings;

/**
 * Locally mint ARK identifiers using a betanumeric counter + check character.
 *
 * ARK format: ark:/NAAN/shoulder + opaque_string + check_character
 * Opaque string is the mint counter encoded in base-29 betanumeric.
 */
class LocalARK implements PIDSelectorInterface
{
    // Betanumeric alphabet: digits + consonants, no vowels, no l (29 characters)
    const ALPHABET = '0123456789bcdfghjkmnpqrstvwxz';

    protected $settings;
    protected $naan;
    protected $shoulder;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
        $this->naan = preg_replace('/\D/', '', $this->settings->get('local_ark_naan', ''));

        $shoulder = $this->settings->get('local_ark_shoulder', '');
        if (empty($shoulder)) {
            $shoulder = $this->randomShoulder();
            $this->settings->set('local_ark_shoulder', $shoulder);
        }
        $this->shoulder = $shoulder;
    }

    public function getLabel()
    {
        return 'Local ARK'; // @translate
    }

    public function mint($targetURI, $itemRepresentation)
    {
        if (empty($this->naan)) {
            return;
        }

        $opaque = $this->randomOpaque(6);
        $opaque .= $this->checkChar($opaque);

        return 'ark:/' . $this->naan . '/' . $this->shoulder . $opaque;
    }

    public function update($existingPID, $targetURI, $itemRepresentation)
    {
        // Local ARKs have no remote service to update; return the existing PID unchanged
        return $existingPID;
    }

    public function delete($pidToDelete)
    {
        // Local ARKs have no remote service to notify on deletion
        return $pidToDelete;
    }

    public function extract($existingFields, $itemRepresentation)
    {
        $prefix = 'ark:/' . $this->naan . '/';
        foreach (explode(',', $existingFields) as $field) {
            $field = trim($field);
            if (array_key_exists($field, $itemRepresentation->values())) {
                $values = $itemRepresentation->value($field, ['all' => true]);
                foreach ($values as $value) {
                    if (strpos((string) $value, $prefix) !== false) {
                        return trim((string) $value);
                    }
                }
            }
        }
        return;
    }

    // Verify the check character of any NOID-style ARK.
    // Parses the shoulder by the ARK spec convention (shoulder ends at the first digit
    // after the NAAN), so this works for EZID ARKs and local ARKs alike.
    public static function verifyArk(string $ark): bool
    {
        // Strip ark:/NAAN/ or ark:NAAN/ prefix (classic and modern forms)
        if (!preg_match('/^ark:\/?(?:\d+)\/(.+)$/', $ark, $matches)) {
            return false;
        }
        $remainder = $matches[1]; // shoulder + opaque + check

        // Shoulder ends at (and includes) the first digit
        if (!preg_match('/^\D*\d/', $remainder, $m)) {
            return false;
        }
        $opaqueAndCheck = substr($remainder, strlen($m[0]));

        if (strlen($opaqueAndCheck) < 2) {
            return false;
        }
        $body  = substr($opaqueAndCheck, 0, -1);
        $check = substr($opaqueAndCheck, -1);

        $alpha = self::ALPHABET;
        $base  = strlen($alpha);
        $sum   = 0;
        for ($i = 0; $i < strlen($body); $i++) {
            $pos = strpos($alpha, $body[$i]);
            if ($pos === false) {
                return false; // character outside betanumeric alphabet
            }
            $sum += ($i + 1) * $pos;
        }
        return $alpha[$sum % $base] === $check;
    }

    // Generate a random 2-character shoulder: consonant + digit.
    // The shoulder must end with a digit so the ARK parser can locate
    // the boundary between shoulder and opaque string.
    private function randomShoulder(): string
    {
        $alpha = self::ALPHABET;
        // Indices 0–9 are digits, 10–28 are consonants
        return $alpha[random_int(10, strlen($alpha) - 1)] . $alpha[random_int(0, 9)];
    }

    // Generate a random betanumeric string of the given length
    private function randomOpaque(int $length): string
    {
        $alpha = self::ALPHABET;
        $max = strlen($alpha) - 1;
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $alpha[random_int(0, $max)];
        }
        return $result;
    }

    // Compute a single check character using a weighted sum (NOID check digit algorithm)
    private function checkChar(string $noid): string
    {
        $alpha = self::ALPHABET;
        $base = strlen($alpha);
        $sum = 0;
        for ($i = 0; $i < strlen($noid); $i++) {
            $pos = strpos($alpha, $noid[$i]);
            $sum += ($i + 1) * $pos;
        }
        return $alpha[$sum % $base];
    }
}
