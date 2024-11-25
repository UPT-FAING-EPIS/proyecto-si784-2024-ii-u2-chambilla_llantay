<?php
include '../config.php'; // Asegúrate de que esta ruta sea correcta


// Obtén el user_id desde la sesión
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Muestra mensajes de error si existen
if (isset($message)) {
    foreach ($message as $msg) {
        echo '
        <div class="message">
            <span>' . $msg . '</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
        </div>
        ';
    }
}
?>

<header class="header">
   <div class="header-1">
      <div class="flex">
         <div class="share">
            <a href="#" class="fab fa-facebook-f"></a>
            <a href="#" class="fab fa-twitter"></a>
            <a href="#" class="fab fa-instagram"></a>
            <a href="#" class="fab fa-linkedin"></a>
         </div>
         <p> nuevo <a href="login.php">ingresar</a> | <a href="register.php">registrar</a> </p>
      </div>
   </div>

   <div class="header-2">
      <div class="flex">
         <a href="home.php" class="logo">Cinemas</a>

         <nav class="navbar">
            <a href="home.php">Inicio </a>
            <a href="about.php">Nosotros</a>
            <a href="shop.php">Tienda</a>
            <a href="contact.php">Contactanos</a>
            <a href="orders.php">Pedidos</a>
         </nav>

         <div class="icons">
            <div id="menu-btn" class="fas fa-bars"></div>
            <a href="search_page.php" class="fas fa-search"></a>
            <div id="user-btn" class="fas fa-user"></div>
            <?php

            // Solo ejecuta la consulta si hay un usuario logueado
            if ($user_id) {
                $select_cart_number = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'") or die('query failed');
                $cart_rows_number = mysqli_num_rows($select_cart_number); 
            } else {
                $cart_rows_number = 0; // Si no hay usuario, el número de artículos es 0
            }
            ?>
            <a href="cart.php"> <i class="fas fa-shopping-cart"></i> <span>(<?php echo $cart_rows_number; ?>)</span> </a>
         </div>

         <div class="user-box">
            <?php if (isset($_SESSION['user_name'])): ?>
                <p>nombre de usuario: <span><?php echo $_SESSION['user_name']; ?></span></p>
                <p>email: <span><?php echo $_SESSION['user_email']; ?></span></p>
                <a href="logout.php" class="delete-btn">cerrar sesion</a>
            <?php endif; ?>
         </div>
      </div>
   </div>
</header>
