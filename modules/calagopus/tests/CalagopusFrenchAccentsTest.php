<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CalagopusFrenchAccentsTest extends TestCase
{
    /** Forms that never exist unaccented in French, so a match is always a defect. Present-tense forms like "il supprime" stay out. */
    private const ALWAYS_ACCENTED = [
        'ete', 'etre', 'etes', 'etat', 'etats', 'meme', 'memes', 'tres', 'apres', 'deja',
        'cle', 'cles', 'acces', 'securite', 'echec', 'echoue', 'reussie',
        'creee', 'creees', 'supprimee', 'supprimees', 'resiliee', 'conservee', 'conservees',
        'duree', 'durees', 'periode', 'systeme', 'systemes', 'numero', 'numeros',
        'donnee', 'donnees', 'parametre', 'parametres', 'necessaire', 'necessaires',
        'telechargement', 'telecharger', 'immediatement', 'reessayez', 'verifiee', 'verifier',
        'configuree', 'configurees', 'generee', 'generees', 'premiere', 'derniere',
    ];

    #[DataProvider('provideFrenchFiles')]
    public function test_french_strings_carry_their_accents(string $file): void
    {
        $offenders = [];

        foreach (file($file) as $number => $line) {
            foreach (self::ALWAYS_ACCENTED as $word) {
                if (preg_match('/\b'.$word.'\b/i', $line)) {
                    $offenders[] = sprintf('%s:%d "%s"', basename($file), $number + 1, $word);
                }
            }
        }

        $this->assertSame([], $offenders, "Unaccented French found:\n".implode("\n", $offenders));
    }

    public static function provideFrenchFiles(): iterable
    {
        foreach (glob(dirname(__DIR__).'/lang/fr/*.php') as $file) {
            yield basename($file) => [$file];
        }
    }
}
