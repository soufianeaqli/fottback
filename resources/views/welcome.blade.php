<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foot - Réservation de terrains</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f0f2f5;
            color: #333;
        }
        .container {
            text-align: center;
            padding: 2rem;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 800px;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        p {
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Bienvenue sur la plateforme de réservation de terrains de foot</h1>
        <p>
            Cette application vous permet de réserver des terrains de football, 
            consulter les disponibilités et gérer vos réservations.
        </p>
        <a href="http://localhost:3000" class="btn">Accéder à l'application</a>
    </div>

    <script>
        // Redirection automatique vers l'application React
        window.location.href = "http://localhost:3000";
    </script>
</body>
</html> 