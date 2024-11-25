<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Login</title>
   <link rel="icon" id="png" href="../images/icon2.png">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link rel="stylesheet" href="../css/style.css"> <!-- Asegúrate de que la ruta sea correcta -->
</head>
<body>

<?php
if (!empty($message)) {
    foreach ($message as $msg) {
        echo '
        <div class="message">
            <span>'.$msg.'</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
        </div>
        ';
    }
}
?>

<div class="form-container">
    <form action="../Controllers/logincontroller.php" method="post">
      <h3>Ingresa</h3>
      <input type="email" name="email" placeholder="Ingresa tu email" required class="box">
      <input type="password" name="password" placeholder="Ingresa tu contraseña" required class="box">
      <input type="submit" name="submit" value="Ingresar" class="btn">
      <p>No tienes cuenta? <a href="register.php">Regístrate ahora</a></p>
   </form>
</div>

</body>
</html>
