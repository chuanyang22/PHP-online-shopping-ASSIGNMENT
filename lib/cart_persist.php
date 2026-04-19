<?php

/**
 * Persist member shopping carts in MySQL so they survive logout/login.
 * Requires table `member_cart` — see database/member_cart.sql
 */

function cart_persist_ready(PDO $pdo): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        $pdo->query('SELECT 1 FROM member_cart LIMIT 1');
        $ready = true;
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

/** @return array<int,int> product_id => quantity */
function cart_load_member_cart(PDO $pdo, int $member_id): array
{
    if (!cart_persist_ready($pdo) || $member_id < 1) {
        return [];
    }
    $stmt = $pdo->prepare('SELECT product_id, quantity FROM member_cart WHERE member_id = ?');
    $stmt->execute([$member_id]);
    $out = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $pid = (int) $row['product_id'];
        $qty = (int) $row['quantity'];
        if ($pid > 0 && $qty > 0) {
            $out[$pid] = $qty;
        }
    }
    return $out;
}

/** @param array<int|string,int> $cart */
function cart_save_member_cart(PDO $pdo, int $member_id, array $cart): void
{
    if (!cart_persist_ready($pdo) || $member_id < 1) {
        return;
    }
    $pdo->prepare('DELETE FROM member_cart WHERE member_id = ?')->execute([$member_id]);
    if (empty($cart)) {
        return;
    }
    $ins = $pdo->prepare('INSERT INTO member_cart (member_id, product_id, quantity) VALUES (?, ?, ?)');
    foreach ($cart as $product_id => $quantity) {
        $pid = (int) $product_id;
        $qty = (int) $quantity;
        if ($pid < 1 || $qty < 1) {
            continue;
        }
        $ins->execute([$member_id, $pid, $qty]);
    }
}

function cart_clear_member_cart(PDO $pdo, int $member_id): void
{
    if (!cart_persist_ready($pdo) || $member_id < 1) {
        return;
    }
    $pdo->prepare('DELETE FROM member_cart WHERE member_id = ?')->execute([$member_id]);
}

/**
 * Merge session cart (e.g. guest items before OTP) with saved DB cart (same keys sum quantities).
 *
 * @param array<int|string,int> $session_cart
 * @param array<int,int>        $db_cart
 *
 * @return array<int,int>
 */
function cart_merge_carts(array $session_cart, array $db_cart): array
{
    $merged = $db_cart;
    foreach ($session_cart as $pid => $qty) {
        $pid = (int) $pid;
        $qty = (int) $qty;
        if ($pid < 1 || $qty < 1) {
            continue;
        }
        $merged[$pid] = ($merged[$pid] ?? 0) + $qty;
    }
    return $merged;
}
