<?php

// Inclure le header
require __DIR__ . '/../partials/header.php';

/**
 * Récupèrer les informations du film
 * (<a href="./index.php?id=10">Lien</a>)
 * 1/ Récupèrer l'id dans l'url
 * 2/ Vérifier que l'id est correct
 * 3/ Exécuter la requête pour récupèrer le film en BDD grâce à l'ID
 * 4/ Si le film existe, on affiche les informations
 * (Une colonne de 6 avec l'image et une colonne de 6 avec le titre et la desc)
 * 5/ Si le film n'existe pas, on affiche un message.
 */

$id = intval($_GET['id'] ?? 0); // Je récupère l'id du film dans l'url et je vérifie

// Exécuter la requête
$movie = $db->query('SELECT * FROM movie WHERE id = '.$id)->fetch();

// Si le film existe
if ($movie) {

    // Ici, on ajoute un commentaire s'il y a un traitement à faire (si le formulaire a été soumis)
    if (!empty($_POST)) {
        $nickname = $_POST['nickname'];
        $message = $_POST['message'];
        $note = $_POST['note'];

        $errors = [];

        if (empty($nickname)) {
            $errors['nickname'] = 'Le pseudo est vide';
        }

        if (strlen($message) < 15) {
            $errors['message'] = 'Le message est trop court';
        }

        if ($note < 0 || $note > 5) {
            $errors['note'] = 'La note n\'est pas valide';
        }

        // On fait la requête
        if (empty($errors)) {
            $query = $db->prepare(
                'INSERT INTO comment (nickname, message, note, created_at, movie_id)
                VALUES (:nickname, :message, :note, NOW(), :movie_id)'
            );
            $query->bindValue(':nickname', $nickname);
            $query->bindValue(':message', $message);
            $query->bindValue(':note', $note);
            $query->bindValue(':movie_id', $movie['id']);
            $query->execute();

            header('Location: movie_single.php?id='.$movie['id']);
        } else {
            /**
             * Afficher les erreurs
             */
            echo '<div class="container alert alert-danger">';
            foreach ($errors as $error) {
                echo '<p class="text-danger m-0">'.$error.'</p>';
            }
            echo '</div>';
        }
    }

?>

<div class="container">
    <div class="row">
        <div class="col-lg-6">
            <img class="img-fluid" src="uploads/<?= $movie['cover']; ?>" alt="<?= $movie['title']; ?>">
        </div>
        <div class="col-lg-6">
            <div class="card shadow-lg rounded-lg">
                <div class="card-body">
                    <h1><?= $movie['title']; ?></h1>
                    <!--
                        Dans la BDD, on stocke la durée sous forme de minutes : 120
                        Sur la fiche du film, il faut afficher 2h00.
                        On va créer une fonction convertToHours(300) -> 5h00
                    -->
                    <p>Durée: <?= convertToHours($movie['duration']); ?></p>

                    <?php
                        // L'objet DateTime
                        $date = new DateTime($movie['released_at']); // Générer la date du film
                        // echo $date->format('d F Y');
                    ?>

                    <p>Sorti le <?= formatFrenchDate($date->format('d F Y')); ?></p>
                    <div>
                        <?= $movie['description']; ?>
                    </div>

                    <?php
                        /**
                         * Pour les acteurs
                         * 1/ On va devoir ajouter des acteurs dans la BDD
                         * 2/ Lier des acteurs à leurs films (table movie_has_actor)
                         * 3/ Modifier le ul ci dessous pour afficher en dynamique les
                         * acteurs de ce film
                         * 4/ BONUS : On pourra cliquer sur un acteur et voir tous les films
                         * dans lesquels il a joué
                         */
                        $actors = $db->query(
                        "SELECT * FROM movie_has_actor
                            INNER JOIN actor ON actor_id = id
                            WHERE movie_id = $id
                        ")->fetchAll();
                    ?>
                    <div class="mt-5">
                        <h5>Avec :</h5>
                        <ul class="list-unstyled">
                            <?php foreach ($actors as $actor) {
                                $fullName = $actor['firstname'].' '.$actor['name'];
                                ?>
                                <li>
                                    <a href="actor_single.php?id=<?= $actor['id']; ?>">
                                        <?= $fullName; ?>
                                    </a>
                                    <a href="https://fr.wikipedia.org/wiki/<?= $fullName; ?>#Filmographie" target="_blank">
                                        (Wikipedia)
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>

                <div class="card-footer">
                    <small class="text-muted">
                        <?php
                            // Je génére un nombre d'étoiles aléatoires
                            $stars = rand(0, 5);
                            // J'affiche mes 5 étoiles
                            for ($i = 1; $i <= 5; $i++) {
                                // J'affiche les étoiles pleines si l'itération est inférieure
                                // au nombre aléatoire $stars
                                if ($i <= $stars) {
                                echo '★ ';
                                } else {
                                echo '☆ ';
                                }
                            }
                        ?>
                    </small>
                </div>
            </div>

            <div class="card shadow-lg rounded-lg mt-5">
                <div class="card-body">
                    <?php
                        // Ici, on récupère les commentaires du film pour les afficher
                        $comments = $db->query('SELECT * FROM comment WHERE movie_id = '.$movie['id']);

                        foreach ($comments as $comment) {
                    ?>

                        <div class="mb-3">
                            <p class="mb-0"><strong><?= $comment['nickname']; ?></strong> <span style="font-size: 10px">le <?= date('d/m/Y à H\hi', strtotime($comment['created_at'])); ?></span></p>
                            <p><?= $comment['message']; ?> <?= $comment['note']; ?>/5</p>
                        </div>
                        <hr>

                    <?php } ?>

                    <form method="POST">
                        <div class="form-group">
                            <label for="nickname">Pseudo</label>
                            <input type="text" class="form-control" name="nickname" id="nickname">
                        </div>

                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea name="message" id="message" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="note">Note</label>
                            <select name="note" id="note" class="form-control">
                                <?php for ($i = 0; $i <= 5; $i++) { ?>
                                    <option value="<?= $i; ?>"><?= $i; ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <button class="btn btn-danger btn-block">Envoyer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
} else { // Si le film n'existe pas
    echo '<div class="alert alert-danger">Ce film n\'existe pas</div>';
}

// Inclure le footer
require __DIR__ . '/../partials/footer.php';
