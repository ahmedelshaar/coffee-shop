<?php

class Orders extends Controller
{
    private $userModel;
    private $orderModel;
    private $productModel;

    public function __construct()
    {
        if (!isLoggedIn()) {
            redirect('/users/login');
        }
        $this->userModel = $this->model('User');
        $this->orderModel = $this->model('Order');
        $this->productModel = $this->model('Product');

    }

    public function index()
    {
        $startDate = $_GET['startDate'] ?? date('Y-m-d', 0);
        $endDate = $_GET['endDate'] ?? date('Y-m-d', time());
        if (empty(trim($endDate))) {
            $endDate = date('Y-m-d', time());
        }
        
        $data = [
            "orders" => $this->orderModel->getAllOrdersWithUsers($startDate, $endDate),
            'products' => $this->productModel->getProducts(),
        ];

        return $this->view('orders/index', $data);
    }

    public function create()
    {
        $data = [
            "products" => $this->productModel->getProducts(),
            "user_last_orders" => [],
            "users" => []
        ];
        if (isAdmin()) {
            $data['users'] = $this->userModel->getUsersByRole('User');
//        } elseif ($_SESSION && $_SESSION['user_role'] == 'User') {
        } elseif (isUser()) {
            $data['user_last_orders'] = $this->userModel->getUserLastOrdersItems(33);
        }
        /*$db->query('SELECT * FROM orders WHERE user_id = :user_id ORDER BY id DESC LIMIT 5');
        $db->bind(':user_id', $_SESSION['user_id']);
        $data['user_last_orders'] = $db->resultSet();*/

        return $this->view('orders/create', $data);
    }

    public function add()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['data'])) {
            $data = json_decode($_GET['data']);
            if ($data->note) {
                $data->note = htmlspecialchars($data->note);
            }
            /*
            $data = json_decode(array_keys($_POST)[0] . '""}}')->data;
            // search for any {} and the content of it in string with reges and display it
            $matches = [];
            preg_match_all('/\{[^}]+\}/', array_keys(array_values($_POST)[0])[0], $matches);
            // convert match into array of objects with json_decode and map
            $data->products = array_map(function ($match) {
                return json_decode($match);
            }, $matches[0]);
//            print_r($data);
//            die();
//            $data = [
//                'user_id' => $_POST['user_id'],
//                'products' => $_POST['products'],
//                'room' => $_POST['room'],
//                'note' => $_POST['note'],
//            ];
            */
            $products_ids = array_map(function ($product) {
                return $product->id;
            }, $data->products);
            $products_data = $this->orderModel->getProductsPrices($products_ids);
            $products = [];
//            print_r($data->products);
            foreach ($products_data as $product) {

                $products[$product->id] = $product;
            }
            if (isUser()) {
                $data->user_id = $_SESSION['user_id'];
            }
            $data->price = 0;
            foreach ($data->products as $key => $product) {
                $data->products[$key]->price = $product->price = $products[$product->id]->price;
                $data->price += $product->price * $product->quantity;
            }
            $data->id = $this->orderModel->addOrder($data);
            $data->status = 'success';
            flash('order_message', "Order #{$data->id} Created with Total invoice {$data->price}L.E", 'success');
            $data = json_encode($data);
            die($data);
        }
    }

    public function deliver()
    {
        if (!isAdmin()) {
            return redirect('orders');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order = $this->orderModel->find($_POST['id']);
            if ($order) {
                $data = ['id' => $_POST['id']];
                if ($order->status === "Processing") {
                    $data['status'] = 'out for delivery';
                } else if ($order->status === "out for delivery") {
                    $data['status'] = 'done';
                }
                $data = $this->orderModel->changeStatus($data);
                if ($data) {
                    flash('order_message', "Status Changed Successfuly", 'success');
                    return redirect('orders');
                }
            } else {
                flash('order_message', 'Order Not Found', 'danger');
                return redirect('orders');
            }
        }
    }

    public function cancel()
    {
        if (!isUser()) {
            return redirect('orders');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order = $this->orderModel->find($_POST['id']);
            if ($order) {
                $data = ['id' => $_POST['id']];
                if ($order->status === "Processing") {
                    $data['status'] = 'Canceled';
                    $data = $this->orderModel->changeStatus($data);
                    if ($data) {
                        flash('order_message', "Status Canceled Successfuly", 'success');
                        return redirect('orders');
                    }
                } else {
                    flash('order_message', 'Can\'t cancel this order', 'danger');
                    return redirect('orders');
                }
            } else {
                flash('order_message', 'Order Not Found', 'danger');
                return redirect('orders');
            }
        }
    }
}
