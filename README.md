# termitus

Programmers' *terminus technicus*.

Translations of some programming terms into Czech language.

Feel free to fork & contribute!

## If you want to add term

Just create file `terms/<term>` (replace `<term>` with your term, e.g. `programming`), commit, push.

Each file consists of one or more definition records. Each record is:

    definition: <your definitin>
    translation: <translation(s)>
    comment: <comment>

Records are separated by `%` on new line.

Example for term `programming`:

    definition: Dělání světa lepším.
    translation: programování
    %
    definition: Zabíjení nudy.
    translation: programování, datlování

When you want to publish termitus somewhere, run:

    $ php build.php

And copy files from directory `site/` onto your FTP.

*(Version of PHP must be greater or equal to 5.3.0.)*

## Credits

- Jakub Kulhan <jakub.kulhan@gmail.com> – singleton
