<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="@@baseUrl/css/main.css">
        <title>M242 | Backend</title>
    </head>
    <body>
        @@auth
        <div class="top">
            <ul>
                <li>
                    <a href="@@baseUrl">Home</a>
                </li>
                <li>
                    <a href="@@baseUrl">Welcome <?= $user->prename ?> <?= $user->lastname ?></a>
                </li>
            </ul>
        </div>
        @@endauth
        @@content
    </body>
</html>
