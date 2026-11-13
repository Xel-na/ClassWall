
<?php

// START SESSION
session_start();

// VISIT COUNTER
if (!isset($_SESSION["visits"])) {
    $_SESSION["visits"] = 1;
} else {
    $_SESSION["visits"]++;
}

// DATABASE CONNECTION
require_once __DIR__ . "/db.php";

// REMEMBERED NAME FROM COOKIE
$rememberedName = $_COOKIE["remembered_name"] ?? "";

// FORM VALUES
$authorValue = $rememberedName;
$bodyValue = "";
$error = "";

// HANDLE POST REQUEST
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $author = trim($_POST["author"] ?? "");
    $body = trim($_POST["body"] ?? "");

    $authorValue = $author;
    $bodyValue = $body;

    // VALIDATION
    if ($author === "" || $body === "") {

        $error = "Please enter your name and message.";

    } else {

        // REMEMBER NAME COOKIE
        if (isset($_POST["remember_name"])) {

            setcookie("remembered_name", $author, [
                "expires" => time() + (30 * 24 * 60 * 60),
                "path" => "/",
                "httponly" => true,
                "samesite" => "Lax"
            ]);

            $rememberedName = $author;

        } else {

            // REMOVE COOKIE IF CHECKBOX IS NOT TICKED
            setcookie("remembered_name", "", [
                "expires" => time() - 3600,
                "path" => "/"
            ]);

            $rememberedName = "";
        }

        // INSERT MESSAGE
        $sql = "INSERT INTO messages (author, body)
                VALUES (:author, :body)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":author" => $author,
            ":body" => $body
        ]);

        // REDIRECT AFTER POST
        header("Location: index.php?posted=1");
        exit;
    }
}

// GET SEARCH KEYWORD
$search = trim($_GET["search"] ?? "");

// SEARCH MESSAGES
if ($search !== "") {

    $sql = "SELECT * FROM messages
            WHERE author ILIKE :search_author
            OR body ILIKE :search_body
            ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);

    $keyword = "%" . $search . "%";

    $stmt->execute([
        ":search_author" => $keyword,
        ":search_body" => $keyword
    ]);

} else {

    // DISPLAY ALL MESSAGES
    $sql = "SELECT * FROM messages
            ORDER BY created_at DESC";

    $stmt = $pdo->query($sql);
}

// FETCH MESSAGES
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassWall - Message Board</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <h1>ClassWall</h1>

    <p>Welcome to our community message board!</p>

    <!-- SESSION COUNTER -->
    <p>
        You've visited <?= $_SESSION["visits"] ?> times.
    </p>

    <hr>

    <!-- POST MESSAGE FORM -->
    <h2>Post a Message</h2>

    <?php if (isset($_GET["posted"])): ?>
        <p>Message posted successfully!</p>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <p>
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="index.php">

        <label for="author">Your Name:</label>
        <br>

        <input
            type="text"
            id="author"
            name="author"
            maxlength="100"
            value="<?= htmlspecialchars($authorValue, ENT_QUOTES, 'UTF-8') ?>"
            required
        >

        <br><br>

        <label for="body">Your Message:</label>
        <br>

        <textarea
            id="body"
            name="body"
            rows="5"
            required
        ><?= htmlspecialchars($bodyValue, ENT_QUOTES, 'UTF-8') ?></textarea>

        <br><br>

        <label>
            <input
                type="checkbox"
                name="remember_name"
                value="1"
                <?= isset($_COOKIE["remembered_name"]) ? "checked" : "" ?>
            >
            Remember my name
        </label>

        <br><br>

        <button type="submit">Post Message</button>

    </form>

    <hr>

    <!-- SEARCH FORM -->
    <h2>Search Messages</h2>

    <form method="GET" action="index.php">

        <input
            type="text"
            name="search"
            placeholder="Search messages..."
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
        >

        <button type="submit">Search</button>

        <a href="index.php">Clear Search</a>

    </form>

    <hr>

    <!-- DISPLAY MESSAGES -->
    <h2>
        <?= $search !== "" ? "Search Results" : "All Messages" ?>
    </h2>

    <?php if (count($messages) > 0): ?>

        <?php foreach ($messages as $message): ?>

            <div>

                <h3>
                    <?= htmlspecialchars($message["author"], ENT_QUOTES, 'UTF-8') ?>
                </h3>

                <p>
                    <?= nl2br(htmlspecialchars($message["body"], ENT_QUOTES, 'UTF-8')) ?>
                </p>

                <small>
                    <?= htmlspecialchars($message["created_at"], ENT_QUOTES, 'UTF-8') ?>
                </small>

            </div>

            <hr>

        <?php endforeach; ?>

    <?php else: ?>

        <p>
            <?= $search !== ""
                ? "No matching messages found."
                : "No messages yet." ?>
        </p>

    <?php endif; ?>

</body>
</html>
