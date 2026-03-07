<?php

namespace App\Service;

class NumberToWordsService
{
    private static $units = [
        0 => 'zéro', 1 => 'un', 2 => 'deux', 3 => 'trois', 4 => 'quatre', 5 => 'cinq',
        6 => 'six', 7 => 'sept', 8 => 'huit', 9 => 'neuf', 10 => 'dix', 11 => 'onze',
        12 => 'douze', 13 => 'treize', 14 => 'quatorze', 15 => 'quinze', 16 => 'seize'
    ];

    public function convert(float $number): string
    {
        $entier = (int)$number;
        $decimales = (int)round(($number - $entier) * 100);

        $texte = $this->convertInt($entier);

        if ($decimales > 0) {
            $texte .= ' et ' . $this->convertInt($decimales) . ' centimes';
        }

        return $texte;
    }

    private function convertInt(int $number): string
    {
        if ($number < 17) {
            return self::$units[$number];
        }

        if ($number < 20) {
            return 'dix-' . self::$units[$number - 10];
        }

        /* === 20 → 69 === */
        if ($number < 70) {
            $tens = ['vingt','trente','quarante','cinquante','soixante'];
            $t = intdiv($number, 10);
            $r = $number % 10;

            $word = $tens[$t - 2];

            if ($r === 1) return $word.'-et-un';
            if ($r > 1) return $word.'-'.self::$units[$r];

            return $word;
        }

        /* === 70 → 79 : soixante + 10 à 19 === */
        if ($number < 80) {
            $r = $number - 60;
            return 'soixante-' . $this->convertInt($r);
        }

        /* === 80 → 99 : quatre-vingt (+ s si 80 exact) === */
        if ($number < 100) {
            $r = $number - 80;
            $base = ($number === 80) ? 'quatre-vingts' : 'quatre-vingt';

            if ($r > 0) {
                return $base . '-' . $this->convertInt($r);
            }
            return $base;
        }

        /* === 100 → 999 === */
        if ($number < 1000) {
            $hundred = intdiv($number, 100);
            $r = $number % 100;

            $word = ($hundred > 1 ? self::$units[$hundred] . ' cent' : 'cent');

            if ($r > 0) return $word . ' ' . $this->convertInt($r);
            return $word;
        }

        /* === 1 000 → 999 999 === */
        if ($number < 1_000_000) {
            $thousand = intdiv($number, 1000);
            $r = $number % 1000;

            $word = ($thousand > 1 ? $this->convertInt($thousand) . ' mille' : 'mille');

            if ($r > 0) return $word . ' ' . $this->convertInt($r);
            return $word;
        }

        /* === 1 000 000 → 999 999 999 === */
        if ($number < 1_000_000_000) {
            $million = intdiv($number, 1_000_000);
            $r = $number % 1_000_000;

            $word = ($million > 1 ? $this->convertInt($million) . ' millions' : 'un million');

            if ($r > 0) return $word . ' ' . $this->convertInt($r);
            return $word;
        }

        /* === 1 000 000 000 → 999 999 999 999 === */
        if ($number < 1_000_000_000_000) {
            $billion = intdiv($number, 1_000_000_000);
            $r = $number % 1_000_000_000;

            $word = ($billion > 1 ? $this->convertInt($billion) . ' milliards' : 'un milliard');

            if ($r > 0) return $word . ' ' . $this->convertInt($r);
            return $word;
        }

        return '';
    }
}
