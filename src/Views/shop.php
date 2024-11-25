<?php
include '../config.php';
session_start();

function checkUserSession() {
    if (!isset($_SESSION['user_id'])) {
        header('location:login.php');
        exit();
    }
}

function addToCart($productData, $conn, $userId) {
    if (!isset($productData['product_name'], $productData['product_price'], $productData['product_image'], $productData['product_quantity'])) {
        return 'Error: datos del producto incompletos.';
    }

    $product_name = mysqli_real_escape_string($conn, $productData['product_name']);
    $product_price = mysqli_real_escape_string($conn, $productData['product_price']);
    $product_image = mysqli_real_escape_string($conn, $productData['product_image']);
    $product_quantity = (int)$productData['product_quantity'];

    // Verificar si el precio es un número
    if (!is_numeric($product_price)) {
        return 'Error: El precio debe ser un número.';
    }

    $check_cart_numbers = mysqli_query($conn, "SELECT * FROM `cart` WHERE name = '$product_name' AND user_id = '$userId'") or die('query failed');

    if (mysqli_num_rows($check_cart_numbers) > 0) {
        return '¡Ya añadido al carrito!';
    } else {
        mysqli_query($conn, "INSERT INTO `cart`(user_id, name, price, quantity, image) VALUES('$userId', '$product_name', '$product_price', '$product_quantity', '$product_image')") or die('query failed');
        return '¡Producto añadido al carrito!';
    }
}

function fetchProducts($conn) {
    $select_products = mysqli_query($conn, "SELECT * FROM `products`") or die('query failed');
    return mysqli_fetch_all($select_products, MYSQLI_ASSOC);
}

checkUserSession();

$message = []; // Cambiado a un array

if (isset($_POST['add_to_cart'])) {
    $message[] = addToCart($_POST, $conn, $_SESSION['user_id']);
}

$products = fetchProducts($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>shop</title>
   <link rel="icon" id="png" href="../user/images/icon2.png">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link rel="stylesheet" href="../../css/style.css">
</head>
<body>

<?php include 'header.php'; ?>

<div class="heading">
   <h3>nuestra tienda</h3>
   <p><a href="home.php">Inicio</a> / tienda </p>
</div>

<section class="products">
   <h1 class="title">últimos productos</h1>
   <div class="box-container">
      <?php  
      if (!empty($products)) {
          foreach ($products as $fetch_products) {
      ?>
      <form action="" method="post" class="box">
          <img class="image" src="../uploaded_img/<?php echo htmlspecialchars($fetch_products['image']); ?>" alt="">
          <div class="name"><?php echo htmlspecialchars($fetch_products['name']); ?></div>
          <div class="price">S/. <?php echo htmlspecialchars($fetch_products['price']); ?> Soles</div>
          <input type="number" min="1" name="product_quantity" value="1" class="qty">
          <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($fetch_products['name']); ?>">
          <input type="hidden" name="product_price" value="<?php echo htmlspecialchars($fetch_products['price']); ?>">
          <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($fetch_products['image']); ?>">
          <input type="submit" value="Añadir al carrito" name="add_to_cart" class="btn">
      </form>
      <?php
          }
      } else {
          echo '<p class="empty">¡Aún no hay productos añadidos!</p>';
      }
      ?>
   </div>
</section>

<?php include 'footer.php'; ?>

<script src="../js/script.js"></script> 

</body>
</html>
