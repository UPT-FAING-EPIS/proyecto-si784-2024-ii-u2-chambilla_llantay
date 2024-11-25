<?php
namespace Views;

require_once '../../Config/Database.php';
require_once '../../Controllers/AdminController.php';
require_once '../../Models/Product.php';

use Config\Database;
use Controllers\AdminController;

session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('location:../auth/login.php');
    exit();
}

$db = new Database();
$conn = $db->connect();
$adminController = new AdminController($conn);

// Manejar adición de productos
if(isset($_POST['add_product'])) {
    $result = $adminController->addProduct($_POST, $_FILES);
    $message[] = $result['message'];
}

// Manejar eliminación
if(isset($_GET['delete'])) {
    $result = $adminController->deleteProduct($_GET['delete']);
    if($result['success']) {
        header('location:admin_products.php');
        exit();
    }
}

// Manejar actualización
if(isset($_POST['update_product'])) {
    $result = $adminController->updateProduct($_POST, $_FILES);
    if($result['success']) {
        header('location:admin_products.php');
        exit();
    }
    $message[] = $result['message'];
}

// Obtener todos los productos
$products = $adminController->getAllProducts();

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>products</title>

   <link rel="icon" id="png" href="images/icon2.png">

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <!-- custom admin css file link  -->
   <link rel="stylesheet" href="../../css/admin_style.css"> <!--link donde se hara la mod-->

</head>
<body>
   
<?php include '../components/admin_header.php'; ?>

<!-- product CRUD section starts  -->

<section class="add-products">

   <h1 class="title">productos de la tienda</h1>

   <form action="" method="post" enctype="multipart/form-data">
      <h3>agregar producto</h3>
      <input type="text" name="name" class="box" placeholder="ingresar nombre del producto" required>
      <input type="number" min="0" name="price" class="box" placeholder="ingresar precio del producto" required>
      <input type="file" name="image" accept="image/jpg, image/jpeg, image/png" class="box" required>
      <input type="submit" value="agregar producto" name="add_product" class="btn">
   </form>

</section>

<!-- product CRUD section ends -->

<!-- show products  -->

<section class="show-products">

   <div class="box-container">

      <?php
         if(count($products) > 0){
            foreach($products as $fetch_products){
      ?>
      <div class="box"> <!--salen img d las pelis -->
         <img src="../../uploaded_img/<?php echo $fetch_products['image']; ?>" alt="">
         <div class="name"><?php echo $fetch_products['name']; ?></div>
         <div class="price">S/. <?php echo $fetch_products['price']; ?> Soles</div>
         <a href="admin_products.php?update=<?php echo $fetch_products['id']; ?>" class="option-btn">actualizar</a>
         <a href="admin_products.php?delete=<?php echo $fetch_products['id']; ?>" class="delete-btn" onclick="return confirm('¿eliminar este producto?');">eliminar</a>
      </div>
      <?php
         }
      }else{
         echo '<p class="empty">¡Aún no hay productos añadidos!</p>';
      }
      ?>
   </div>

</section>

<section class="edit-product-form">

   <?php
      if(isset($_GET['update'])){
         $update_id = $_GET['update'];
         $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
         $stmt->execute([$update_id]);
         if($stmt->rowCount() > 0){
            while($fetch_update = $stmt->fetch(\PDO::FETCH_ASSOC)){
   ?>
   <form action="" method="post" enctype="multipart/form-data">
      <input type="hidden" name="update_p_id" value="<?php echo $fetch_update['id']; ?>">
      <input type="hidden" name="update_old_image" value="<?php echo $fetch_update['image']; ?>">
      <img src="../../uploaded_img/<?php echo $fetch_update['image']; ?>" alt="">
      <input type="text" name="update_name" value="<?php echo $fetch_update['name']; ?>" class="box" required placeholder="enter product name">
      <input type="number" name="update_price" value="<?php echo $fetch_update['price']; ?>" min="0" class="box" required placeholder="enter product price">
      <input type="file" class="box" name="update_image" accept="image/jpg, image/jpeg, image/png">
      <input type="submit" value="actualizar" name="update_product" class="btn">
      <input type="reset" value="cancel" id="close-update" class="option-btn">
   </form>
   <?php
         }
      }
      }else{
         echo '<script>document.querySelector(".edit-product-form").style.display = "none";</script>';
      }
   ?>

</section>







<!-- custom admin js file link  -->
<script src="../js/admin_script.js"></script>

</body>
</html>