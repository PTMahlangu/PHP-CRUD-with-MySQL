

<?php

    class ShoppingCart {
        
        protected $shoppingCart = array(); 
// -----------------------------------------------------------construct()--------------------------------   
        public function __construct(){ 
            if (!session_id()) {
                session_start();
                //  $_SESSION['cart'] =array();
            }

            $this->shoppingCart  = $_SESSION['cart'];
           
        } // end of __construct

// -----------------------------------------------------------getAllItems()--------------------------------
        public function getAllItems(){
            include_once 'DBConn.php';
            // select all items from databas
            $stmt = $conn->prepare("SELECT * from tbl_Item");
            $stmt->execute();
            $AllItems =$stmt->get_result();
            return $AllItems;
        }// end of getAllItems

// -----------------------------------------------------------getAllCartItems()--------------------------------
        public function getAllCartItems(){
            return $this->shoppingCart;
        }// end of getAllCartItems
    
// ----------------------------------------------------------- getTotalItem()--------------------------------
        public function getTotalItem(){
            if (isset($this->shoppingCart)){
                $CartItemCount= count($this->shoppingCart);
                return $CartItemCount;
            }
            else{
                $CartItemCount= 0;
                return $CartItemCount;
            }
        }// end of getTotalItem

// -----------------------------------------------------------AddItermToCart()--------------------------------
        public function AddItermToCart(){

            $ItemID = $_POST['ItemID']; 
            $Description = $_POST['Description']; 
            $Quantity = '1';             // default quantity
            $SellPrice= $_POST['SellPrice'];
        
            $item = array('ItemID'=>$ItemID,'Description'=>$Description,'SellPrice'=>$SellPrice,'Quantity'=>$Quantity);

            if (isset($this->shoppingCart)){

                if (in_array($item, $this->shoppingCart)) {
                    echo "Product already in cart";
                    
                } else {
                    array_push($this->shoppingCart,$item); 
                    print "Product added cart";
                    
                }
            }else{
                array_push($this->shoppingCart,$item);
                print "Product added cart"; 
            }
            $_SESSION['cart'] = $this->shoppingCart;
        }// end of AddItermToCart

// -----------------------------------------------------------removeItem()--------------------------------
        public function removeItem($ItemID){

            foreach ($this->shoppingCart as $subkey=>$subarray){
                    if ($subarray['ItemID']==$ItemID ){
                    unset($this->shoppingCart[$subkey]);
                    echo 'Product removed <br>'; 
                }
            }
            $_SESSION['cart'] = $this->shoppingCart;
        }// end of removeItem

// -----------------------------------------------------------incrementQtyInCart()--------------------------------
        public function incrementQtyInCart($ItemID){

            foreach ($this->shoppingCart as $subkey=>$subarray){
                    if ($subarray['ItemID']==$ItemID ){
                    break;
                }
            }
            $increment =$this->shoppingCart[$subkey]['Quantity'];
            $increment +=1;
            $this->shoppingCart[$subkey]['Quantity'] =$increment;
             $_SESSION['cart'] = $this->shoppingCart;
        }// end of incrementQtyInCart

// -----------------------------------------------------------decrementQtyInCart()--------------------------------
        public function decrementQtyInCart($ItemID){

            foreach ($this->shoppingCart as $subkey=>$subarray){
                    if ($subarray['ItemID']==$ItemID ){
                    break;
                }
            }
            $decrement =$this->shoppingCart[$subkey]['Quantity'];
            if($decrement > 1){
                $decrement -=1;
            }else{
                $decrement =1;
            }
            
            $this->shoppingCart[$subkey]['Quantity'] =$decrement;
             $_SESSION['cart'] = $this->shoppingCart;
        }


// -----------------------------------------------------------emptyCart()--------------------------------
        public function emptyCart(){
            $this->shoppingCart=array();
            $_SESSION['cart'] = $this->shoppingCart;
        }// end of decrementQtyInCart

// -----------------------------------------------------------checkout()-------------------------------- 
        public function checkout($Shipping_Address){
            include_once 'DBConn.php';
            // get user name from session
            $FirstName = $_SESSION["user"];
            // get user firstname and suername from tlb_user table
            $results_ = $conn->prepare("SELECT * FROM `tbl_user` WHERE Firstname='$FirstName' ") or 
            die($conn->error);
            $results_->execute();
            $results =$results_->get_result()->fetch_assoc();
           
            $Customer_Firstname = $results['Firstname'];
            $Customer_Surname = $results['Surname'];
            
            // save user details to tbl_custumer   
            $conn->query("INSERT INTO tbl_customer (Customer_Name,Customer_Surname,Billing_Address) VALUES('$Customer_Firstname','$Customer_Surname','$Shipping_Address')") or
            die($conn->error);
            $Customer_id=$conn->insert_id; // get custumer_id 

            // generate order numer 
            $conn->query("INSERT INTO tbl_order (Customer_id,Order_date,Shipping_Address) VALUES($Customer_id,CURRENT_TIMESTAMP,'$Shipping_Address')") or
            die($conn->error);
            $Order_id =$conn->insert_id; //get order_id
            // save ordered items to databse in tbl_order_item  table 
            foreach ($this->shoppingCart as $items) {
                $ItemID   =$items['ItemID'];
                $Quantity =$items['Quantity'];
                $conn ->query("INSERT INTO tbl_order_item (Order_id,Item_id,Quantity) VALUES($Order_id,$ItemID,$Quantity)") or
                die($conn->error);  
            }
            $cart = new ShoppingCart();
            $cart ->emptyCart(); // empty cart
            return ("Your order has been recorded. Your order number is: ".$Order_id); 
        }// end of checkout

// -----------------------------------------------------------insertItemInDb()-------------------------------- 
        public function insertItemInDb( $item = array()){
            include_once 'DBConn.php';
            if(!is_array($item) or count($item) === 0){ 
                return FALSE; 
            }
            $ItemID =$item['ItemID'];
            $Description =$item['Description'];
            $Quantity =$item['Quantity'];
            $CostPrice =$item['CostPrice'];
            $Quantity =$item['Quantity'];
            $SellPrice =$item['SellPrice'];
            // insert items to tbl_Item table 
            $conn ->query("INSERT INTO tbl_Item (ItemID,Description,CostPrice,Quantity,SellPrice) VALUES($ItemID,'$Description',$CostPrice,$Quantity, $SellPrice)") or 
            die($conn->error);
       }// end of insertItemInDb


// -----------------------------------------------------------removeItemInDbb()-------------------------------- 
        public function removeItemInDb($ItemID){
            include_once 'DBConn.php';
            // remove item from database by itemid
            $conn->query("DELETE FROM `tbl_Item` WHERE `tbl_Item`.`ItemID` = $ItemID") or 
            die($conn->error);
        }// end of removeItemInDb


// -----------------------------------------------------------updateItemInDb()-------------------------------- 
        public function updateItemInDb($ItemID,$Description,$CostPrice,$Quantity,$SellPrice){
            include_once 'DBConn.php';
            // update datebase item
            $conn->query("UPDATE `tbl_Item` SET `Description`= '$Description',`CostPrice` = $CostPrice, `Quantity` = $Quantity, `SellPrice` = $SellPrice WHERE `tbl_Item`.`ItemID` = $ItemID")  
            or die($conn->error);
        }    // end of updateItemInD

// -----------------------------------------------------------login()--------------------------------..........--- 
        public function login($email,$password){
            include_once 'DBConn.php';
            // get user infor by email
            $results_ = $conn->prepare("SELECT * FROM `tbl_user` WHERE Email='$email' ") or 
            die($conn->error);
            $results_->execute();
            $results =$results_->get_result()->fetch_assoc();
            // check if password match
            if ( isset($results) && ($results['Password']==$password)){
                // Store data in session variables
                $_SESSION["loggedin"] = true;
                $_SESSION["user"] =$results['Firstname'];
                // Redirect user to checkout page

                if($_SESSION['checkout'] =="on"){
                    header("location: checkout.php");
                }
                else if($_SESSION['login']="off"){
                    header("location: admin.php");
                }
                
            }else{
                // Redirect user to login page
                header("location: login_page.php");
            }
        }// end of login

// -----------------------------------------------------------register()-------------------------------- 
        public function register($FirstName,$Surname,$Email,$Passkey){
            include_once 'DBConn.php';
            // register user to database
            $conn ->query("INSERT INTO tbl_user (Firstname,Surname,Email,Password) VALUES('$FirstName','$Surname','$Email','$Passkey' )") or 
            die($conn->error);
            // Store data in session variables
            $_SESSION["loggedin"] = true;
            $_SESSION["user"] =$FirstName;
            // Redirect user to checkout page
            if($_SESSION['checkout'] =="on"){
                header("location: checkout.php");
            }
            else if($_SESSION['login']="off"){
                header("location: admin.php");
            }
        } // end of register

    }  // end of class
// ******************************************************************END OF SHOPPING CLASSS************************************************* 


// check is is delete post
    if(isset($_POST['delete'])){ 
        $cart = new ShoppingCart();
        $ItemID = $_POST['ItemID']; 
        $cart->removeItem($ItemID);
        $cart_count =$cart->getTotalItem();
        // Redirect user to showcart page
        header("Location:ShowCart.php");
    }

//  chech if is emptycart post
    if(isset($_POST['emptycart'])){ 
        $cart = new ShoppingCart();
        $cart->emptyCart();
        $cart_count =$cart->getTotalItem();
        // Redirect user
        header("Location:ShowCart.php");
    }

//  chech if is AddToCart post
    if(isset($_POST['AddToCart'])){ 
        $cart = new ShoppingCart();
        $cart->AddItermToCart();
        // Redirect user
        header("Location:myShop.php");
    }

//  chech if is decrementCart post
    if(isset($_POST['decrementCart'])){  print_r($Password);
        $cart = new ShoppingCart();
        $ItemID = $_POST['ItemID'];
        $cart->decrementQtyInCart($ItemID);
    }

//  chech if is incrementCart post
    if(isset($_POST['incrementCart'])){ 
        $cart = new ShoppingCart();
        $ItemID = $_POST['ItemID'];
        $cart->incrementQtyInCart($ItemID);
    }

//  chech if is checkoutpost
    if(isset($_POST['checkout'])){  print_r($Password);
        if (isset($_SESSION["user"])){
            // Redirect user
            header("location: checkout.php");
        }else{
            // Redirect user
            header("location: login_page.php");
        }
    }

//  chech if is delete database post
    if(isset($_POST['deleteInDb'])){ 
        $cart = new ShoppingCart();
        $ItemID = $_POST['ItemID'];
        $cart->removeItemInDb($ItemID);
        // Redirect user
        header("Location:admin.php");
    }

//  chech if is edit database post
    if(isset($_POST['editInDb'])){ 
        $cart = new ShoppingCart();
        $ItemID = $_POST['product_id'];
        $Item_desc = $_POST['product_desc'];
        $Item_costprice = $_POST['product_costprice'];
        $Item_sellprice = $_POST['product_sellprice'];
        $Item_qty = $_POST['product_qty'];
    //  check if  image is posted
        if(isset($_FILES['product_image'])){
            $file_name = $_FILES['product_image']['name'];
            $file_tmp =$_FILES['product_image']['tmp_name'];
            if(empty($errors)==true){
                move_uploaded_file($file_tmp,"images/".$Item_desc.'.jpg'); //save images as jpg under /images/ dir
            }
        }
        $cart->updateItemInDb($ItemID,$Item_desc,$Item_costprice,$Item_qty,$Item_sellprice);
        // Redirect user admin page
        header("Location:admin.php");
    }

//  chech if is add product to database post
    if(isset($_POST['addItemInDb'])){ 
        $cart = new ShoppingCart();
        $ItemID = $_POST['product_id'];
        $Item_desc = $_POST['product_desc'];
        $Item_costprice = $_POST['product_costprice'];
        $Item_sellprice = $_POST['product_sellprice'];
        $Item_qty = $_POST['product_qty'];
     //  check if  image is posted
        if(isset($_FILES['product_image'])){
            $file_name = $_FILES['product_image']['name'];
            $file_tmp =$_FILES['product_image']['tmp_name'];
            move_uploaded_file($file_tmp,"images/".$Item_desc.'.jpg'); //save images as jpg under /images/ dir
        } 
        $items = array('ItemID'=>$ItemID,'Description'=>$Item_desc,'CostPrice'=>$Item_costprice,'SellPrice'=>$Item_sellprice,'Quantity'=>$Item_qty);
        $cart->insertItemInDb($items);
        // Redirect user
        header("Location:admin.php");
    }

//  chech if is redister post
    if(isset($_POST['register'])){ 
        $cart = new ShoppingCart();
        $FirstName = $_POST['FirstName'];
        $Surname = $_POST['Surname'];
        $Email= $_POST['Email'];
        $Password = $_POST['Password'];
        
        if( !empty($FirstName) and !empty($Surname) and !empty($Email) and !empty($Password) ){
             $cart->register($FirstName,$Surname,$Email,$Password);
        }else{
            // Redirect user
            header("Location:register_page.php");
        }

    }

//  chech if is logging post
    if(isset($_POST['login'])){ 
        $cart = new ShoppingCart();
        $User_Email = $_POST['Email'];
        $User_Password = $_POST['Password'];
        $cart->login($User_Email,$User_Password);
    }

//  chech if is logout post
    if(isset($_POST['logout'])){ 
        session_unset();
   session_write_close();
    header("Location:admin.php");
    exit;
}


?>


