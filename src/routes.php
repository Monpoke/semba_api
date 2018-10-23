<?php

use Slim\Http\Request;
use Slim\Http\Response;

/**
 * GET ALL PRODUCTS
 */
$app->get('/products', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Get products");


    // OUTPUT ARRAY
    $outputProducts = [];

    /** @var PDO $db */
    $db = $this->db;

    $prepare = $db->prepare("SELECT * FROM products ORDER BY pid");
    $prepare->execute();

    /**
     * Foreach on each result.
     */
    while ($r = $prepare->fetch(PDO::FETCH_ASSOC)) {

        $outputProducts[] = $r;

    }


    // Render index view
    return $response->withJson(['status' => 0, 'products' => $outputProducts]);
});

/**
 * GET ONE PRODUCT
 */
$app->get('/product/{pid:[0-9]+}', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Get infos for product " . $args['pid']);


    // OUTPUT ARRAY
    $outputProducts = [];

    /** @var PDO $db */
    $db = $this->db;

    $prepare = $db->prepare("SELECT * FROM products WHERE pid = :pid");
    $prepare->bindParam('pid', $args['pid']);
    $prepare->execute();

    // No data, exit
    if ($prepare->rowCount() == 0) {
        return $response->withJson(['status' => 1, 'msg' => 'unknown'], 404);
    }


    // Render index view
    return $response->withJson(['status'=>0,'product'=>$prepare->fetch(PDO::FETCH_ASSOC)]);
});


/**
 * SCAN OBJECT.
 */
$app->put('/scan', function (Request $request, Response $response, array $args) {

    $this->logger->info("Scan a new product");


    /** @var PDO $db */
    $db = $this->db;
    $statusOutput = [
        'status' => 0
    ];

    /**
     * CHECK DATA
     */
    $barcode = $request->getParsedBodyParam('barcode');
    $quantity = $request->getParsedBodyParam('quantity');

    if (empty($barcode)) {
        return $response->withJson([
            'status' => 1,
            'msg' => 'Empty barcode'
        ]);
    }


    // PARSE QUANTITY
    if ($quantity == null)
        $quantity = 1;
    else {
        $quantity = abs(intval($quantity));
        if ($quantity == 0)
            $quantity = 1;
    }


    /**
     * CHECK PRODUCT EXISTS
     */
    $selectProductsStmt = $db->prepare("SELECT COUNT(pid) as cnt FROM products WHERE barcode = :barcode");
    $selectProductsStmt->bindParam('barcode', $barcode);
    $selectProductsStmt->execute();

    $r = $selectProductsStmt->fetch(PDO::FETCH_OBJ);

    // NEW INSERT
    if ($r->cnt == 0) {
        // update quantity
        $insertQte = $db->prepare("INSERT INTO products (quantity, barcode) VALUES(:qte, :barcode)");
        $insertQte->execute([
            'qte' => $quantity,
            'barcode' => $barcode
        ]);
        $this->logger->info("Product insersed!");
        $statusOutput['msg'] = "i";

    } else {
        // update quantity
        $updateQtePrepare = $db->prepare("UPDATE products SET quantity = quantity + :qte WHERE barcode = :barcode");
        $updateQtePrepare->execute([
            'qte' => $quantity,
            'barcode' => $barcode
        ]);
        $statusOutput['msg'] = "u";
    }

    $this->logger->info("Product=[qte:" . $quantity . ", barcode=" . $barcode . "]");


    // Render index view
    return $response->withJson($statusOutput);
});

/**
 * UPDATE OBJECT.
 */
$app->post('/product/{pid:[0-9]+}', function (Request $request, Response $response, array $args) {

    $this->logger->info("Update a product.");


    /** @var PDO $db */
    $db = $this->db;
    $statusOutput = [
        'status' => 0
    ];




    /**
     * CHECK PRODUCT EXISTS
     */
    $selectProductsStmt = $db->prepare("SELECT * FROM products WHERE pid = :pid");
    $selectProductsStmt->bindParam('pid', $args['pid']);
    $selectProductsStmt->execute();

    $r = $selectProductsStmt->fetch(PDO::FETCH_ASSOC);

    // NEW INSERT
    if ($selectProductsStmt->rowCount() == 0) {
        return $response->withJson(['status' => 0, 'msg' => 'unknown'],404);
    }


    /**
     * CHECK DATA AND MERGE RESULTS
     */
    $title = $request->getParsedBodyParam('title');
    $comment = $request->getParsedBodyParam('comment');
    $quantity = $request->getParsedBodyParam('quantity');

    if($title !== null){
        $r['title'] = filter_var($title, FILTER_SANITIZE_STRING);
    }
    if($comment !== null){
        $r['comment'] = filter_var($comment, FILTER_SANITIZE_STRING);
    }
    if($quantity !== null){
        $quantity = intval($quantity);
        if($quantity<0){
            $quantity=0;
        }
        $r['quantity'] = filter_var($quantity, FILTER_SANITIZE_NUMBER_INT);
    }



    $updateSQL = $db->prepare("UPDATE products SET quantity = :quantity, comment = :comment, title = :title WHERE pid=:pid");
    $updateSQL->execute([
        'quantity' => $r['quantity'],
        'comment' => $r['comment'],
        'title' => $r['title'],
        'pid' => $args['pid']
    ]);


    $this->logger->info("update product with pid " . $args['pid']);

    $statusOutput['product']=$r;

    // Render.
    return $response->withJson($statusOutput);
});


/**
 * UPDATE OBJECT.
 */
$app->get('/products/erase', function (Request $request, Response $response, array $args) {

    $this->logger->info("Erase all products.");


    /** @var PDO $db */
    $db = $this->db;
    $statusOutput = [
        'status' => 0
    ];




    /**
     * CHECK PRODUCT EXISTS
     */
    $pre = $db->prepare("DELETE FROM products WHERE 1=1");
    if(!$pre->execute()){
        $statusOutput['status']=1;
    }

    // Render.
    return $response->withJson($statusOutput);
});


