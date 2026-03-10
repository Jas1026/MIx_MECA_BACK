<?php

function descontarIngredientes($pdo, $product_id, $quantity){

    // obtener ingredientes de la receta
    $stmt = $pdo->prepare("
        SELECT ingredient_id, cant_us
        FROM recipes
        WHERE product_id = ?
    ");

    $stmt->execute([$product_id]);
    $ingredientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($ingredientes as $ing){

        $ingredient_id = $ing['ingredient_id'];
        $cantidad = $ing['cant_us'] * $quantity;

        // ver tipo ingrediente
        $stmtTipo = $pdo->prepare("
            SELECT tipo 
            FROM ingredients 
            WHERE id_ingredients=?
        ");

        $stmtTipo->execute([$ingredient_id]);
        $tipo = $stmtTipo->fetchColumn();

        if($tipo == "normal"){

            descontarNormal($pdo, $ingredient_id, $cantidad);

        }else{

            descontarBotella($pdo, $ingredient_id, $cantidad);

        }

    }

}

function descontarNormal($pdo, $ingredient_id, $cantidad){

    $stmt = $pdo->prepare("
        UPDATE ingredients
        SET stock_act = stock_act - ?
        WHERE id_ingredients = ?
    ");

    $stmt->execute([$cantidad, $ingredient_id]);

}

function descontarBotella($pdo, $ingredient_id, $cantidad){

    while($cantidad > 0){

        // buscar botella con menos contenido (la más vacía)
        $stmt = $pdo->prepare("
            SELECT id_bottle, peso_actual
            FROM ingredient_bottles
            WHERE ingredient_id = ?
            AND peso_actual > 0
            ORDER BY peso_actual ASC
            LIMIT 1
        ");

        $stmt->execute([$ingredient_id]);
        $botella = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$botella){
            break; // no hay botellas
        }

        $usar = min($botella['peso_actual'], $cantidad);

        $stmtUpdate = $pdo->prepare("
            UPDATE ingredient_bottles
            SET peso_actual = peso_actual - ?
            WHERE id_bottle = ?
        ");

        $stmtUpdate->execute([$usar, $botella['id_bottle']]);

        $cantidad -= $usar;

    }

}