<?php
namespace Views;

require_once '../../Config/Database.php';
require_once '../../Controllers/AdminController.php';

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


if(isset($_POST['update_order'])){
   $order_update_id = $_POST['order_id'];
   $update_payment = $_POST['update_payment'];
   
   if($adminController->updateOrderStatus($order_update_id, $update_payment)) {
       $message[] = 'El estado del pago ha sido actualizado!';
   }
}

if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   if($adminController->deleteOrder($delete_id)) {
       header('location:admin_orders.php');
   }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Pedidos</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link rel="stylesheet" href="../../css/admin_style.css">
</head>
<body>
   
<?php include '../components/admin_header.php'; ?>

<section class="orders">
   <h1 class="title">pedidos realizados</h1>

   <div class="box-container">
      <?php
      $orders = $adminController->getAllOrders();
      if(!empty($orders)){
         foreach($orders as $order){
      ?>
      <div class="box">
         <p> user id : <span><?php echo $order['user_id']; ?></span> </p>
         <p> estimado : <span><?php echo $order['placed_on']; ?></span> </p>
         <p> nombre : <span><?php echo $order['name']; ?></span> </p>
         <p> numero : <span><?php echo $order['number']; ?></span> </p>
         <p> email : <span><?php echo $order['email']; ?></span> </p>
         <p> direccion : <span><?php echo $order['address']; ?></span> </p>
         <p> productos totales : <span><?php echo $order['total_products']; ?></span> </p>
         <p> Precio total : <span>S/. <?php echo $order['total_price']; ?> soles</span> </p>
         <p> Método de pago : <span><?php echo $order['method']; ?></span> </p>
         <form action="" method="post">
            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
            <select name="update_payment">
               <option value="" selected disabled><?php echo $order['payment_status']; ?></option>
               <option value="pending">pending</option>
               <option value="completed">completed</option>
            </select>
            <input type="submit" value="actualizar" name="update_order" class="option-btn">
            <a href="admin_orders.php?delete=<?php echo $order['id']; ?>" 
               onclick="return confirm('¿Eliminar este pedido?');" 
               class="delete-btn">eliminar</a>
         </form>
      </div>
      <?php
         }
      }else{
         echo '<p class="empty">¡Aún no hay pedidos realizados!</p>';
      }
      ?>
   </div>
</section>

<script src="../../js/admin_script.js"></script>

</body>
</html>