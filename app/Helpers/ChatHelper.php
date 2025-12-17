<?php

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

if (!function_exists('renderMentions')) {
    function renderMentions(string $text, $currentUser)
    {
        // Normalisasi identitas user
        $myUsername = Str::lower(
            $currentUser->username
            ?? Str::slug($currentUser->name)
        );

        return new HtmlString(
            preg_replace_callback(
                '/@([\w\-]+)/',
                function ($matches) use ($myUsername) {
                    $mentioned = Str::lower($matches[1]);

                    // mention ke diri sendiri
                    if ($mentioned === $myUsername) {
                        return '<span class="mention mention-me">@' . e($matches[1]) . '</span>';
                    }

                    // mention user lain
                    return '<span class="mention">@' . e($matches[1]) . '</span>';
                },
                e($text)
            )
        );
    }
}
