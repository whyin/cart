<?php
$hostname = "127.0.0.1:3307";
$username = "root";
$password = "0000";

// MySQL 연결
$conn = new mysqli($hostname, $username, $password, "products");
$conn->set_charset("utf8"); // 한글 깨짐 방지

// 각 item_num에 대한 상품 데이터를 가져오는 함수
function getProduct($conn, $item_num) {
    $sql = "SELECT * FROM item WHERE NUMBER='$item_num'";
    $result = mysqli_query($conn, $sql);
    $res_row = array();
    if ($result) {
        while ($row = mysqli_fetch_array($result)) {
            array_push($res_row, array(
                'number' => $row[0],    // 번호
                'engname' => $row[1],   // 영어 이름
                'korname' => $row[2],   // 한글 이름
                'info' => $row[3],      // 정보
                'image' => $row[4],     // 이미지
                'price' => $row[5]      // 가격
            ));
        }
    }
    return $res_row;
}

// 장바구니에 추가할 항목들 (여기서는 1, 2, 3번 항목)
$product1 = getProduct($conn, 1);
$product2 = getProduct($conn, 2);
$product3 = getProduct($conn, 3);

$conn->close();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>장바구니</title>
    <style>
        /* 동일한 스타일 적용 */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        header {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 1em;
        }
        main {
            padding: 2em;
        }
        .cart-container {
            background-color: white;
            padding: 2em;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        .cart-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #f9f9f9;
            padding: 1em;
            margin-bottom: 1em;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .cart-item img {
            width: 60px;
            height: auto;
            margin-right: 1em;
        }
        .cart-item-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-grow: 1;
        }
        .cart-item-name {
            font-size: 1.2em;
            margin-right: 2em; 
        }
        .quantity-controls {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }
        .quantity-controls button {
            padding: 0.5em;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            margin: 0 0.5em;
        }
        .quantity-controls button:hover {
            background-color: #45a049;
        }
        .cart-item-price {
            font-weight: bold;
            color: #333;
            margin-left: 1em;
            margin-right: 1em;
            width: 100px;
            text-align: right;
        }
        .cart-total {
            font-weight: bold;
            text-align: right;
            margin-top: 1em;
        }
        .checkout-btn {
            width: 100%;
            padding: 1em;
            background-color: #333;
            color: white;
            border: none;
            cursor: pointer;
            margin-top: 1em;
            text-align: center;
            border-radius: 10px;
        }
        .checkout-btn:hover {
            background-color: #555;
        }
    </style>
</head>
<body>
    <header>
        <h1>장바구니</h1>
    </header>
    <main>
        <div class="cart-container">
            <!-- 장바구니 항목들이 여기에 추가됩니다 -->
            <div id="cart-items"></div>

            <div class="cart-total">
                합계 금액: <span id="cart-total">0</span> 원
            </div>
            <button class="checkout-btn" onclick="checkout()">결제하기</button>
        </div>
    </main>

    <script>
        let cart = [];
        // 서버에서 PHP로 전달된 상품 데이터를 자바스크립트로 전달
        const products = {
            1: <?php echo json_encode($product1); ?>,
            2: <?php echo json_encode($product2); ?>,
            3: <?php echo json_encode($product3); ?>
        };

        // 장바구니에 상품을 추가하는 함수
        function addToCart(product) {
            // 장바구니에 이미 있는 상품인지 확인
            const existingItem = cart.find(item => item.number === product.number);

            // 상품이 이미 장바구니에 있다면 수량을 증가시키고, 없으면 새롭게 추가
            if (existingItem) {
                existingItem.quantity += 1;  // 이미 있으면 수량 +1
            } else {
                product.quantity = 1;  // 장바구니에 없으면 수량을 1로 설정
                cart.push(product);  // 새 상품을 장바구니에 추가
            }

            renderCart();  // 장바구니 상태를 화면에 업데이트
        }

        // 상품의 수량을 변경하는 함수
        function changeQuantity(productNumber, amount) {
            // 장바구니에서 해당 상품을 찾음
            const item = cart.find(item => item.number === productNumber);

            if (item) {
                item.quantity += amount;  // 수량 변경

                // 수량이 0 이하가 되면 장바구니에서 제거
                if (item.quantity <= 0) {
                    cart = cart.filter(item => item.number !== productNumber);
                }
            }

            renderCart();  // 장바구니 상태를 다시 렌더링
        }

        // 장바구니 내용을 렌더링하는 함수
        function renderCart() {
            const cartItemsContainer = document.getElementById('cart-items');
            cartItemsContainer.innerHTML = '';  // 기존 장바구니 내용을 초기화

            let total = 0;  // 총 가격 초기화

            // 장바구니에 있는 모든 상품을 화면에 표시
            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;  // 해당 상품의 총 가격
                total += itemTotal;  // 총 가격에 해당 상품 가격을 더함

                const cartItem = document.createElement('div');
                cartItem.className = 'cart-item';

                // 장바구니 항목을 HTML로 구성
                cartItem.innerHTML = `
                    <img src="${item.image}" alt="${item.engname}">
                    <div class="cart-item-info">
                        <div class="cart-item-name">${item.korname}</div>
                        <div class="quantity-controls">
                            <button onclick="changeQuantity(${item.number}, -1)">-</button>
                            <span>${item.quantity}</span>
                            <button onclick="changeQuantity(${item.number}, 1)">+</button>
                        </div>
                    </div>
                    <div class="cart-item-price">${itemTotal} 원</div>
                `;

                cartItemsContainer.appendChild(cartItem);  // 항목을 장바구니 목록에 추가
            });

            // 총 가격을 화면에 표시
            document.getElementById('cart-total').textContent = total;
        }

        // 결제하기 버튼 클릭 시 총 결제 금액을 알리는 함수
        function checkout() {
            alert(`총 결제 금액은 ${document.getElementById('cart-total').textContent} 원입니다.`);
        }

        // PHP로부터 전달된 각 상품을 장바구니에 추가
        // 처음에는 각 상품이 자동으로 장바구니에 추가되도록 설정
        addToCart(products[1][0]);  // 1번 항목 추가
        addToCart(products[2][0]);  // 2번 항목 추가
        addToCart(products[3][0]);  // 3번 항목 추가
    </script>
</body>
</html>