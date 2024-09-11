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

// CSV 파일을 처리하는 함수
function processCSV($file, $conn) {
    $csvData = array();
    if (($handle = fopen($file, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // CSV에서 item_num 값을 가져와 DB에서 상품 정보 불러오기
            $item_num = $data[0];  // 첫 번째 열이 item_num이라고 가정
            $product = getProduct($conn, $item_num);
            
            // 불러온 상품 정보를 배열에 추가
            if (!empty($product)) {
                array_push($csvData, $product[0]); // getProduct는 배열을 반환하므로 [0]으로 접근
            }
        }
        fclose($handle);
    }
    return $csvData;
}

// CSV 파일이 업로드된 경우 처리
$csvData = array();
if (isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $csvData = processCSV($file, $conn);  // DB에서 상품 정보를 가져와 배열로 저장
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>장바구니</title>
    <style>
        /* 스타일 동일, 생략 */
    </style>
</head>
<body>
    <header>
        <h1>장바구니</h1>
    </header>
    <main>
        <div class="cart-container">
            <div id="cart-items"></div>

            <div class="cart-total">
                합계 금액: <span id="cart-total">0</span> 원
            </div>
            <button class="checkout-btn" onclick="checkout()">결제하기</button>

            <!-- CSV 파일 업로드 폼 -->
            <form action="" method="post" enctype="multipart/form-data">
                <input type="file" name="csv_file" accept=".csv" />
                <button type="submit">CSV 파일 업로드</button>
            </form>
        </div>
    </main>

    <script>
        let cart = [];
        
        // CSV 파일에서 가져온 상품 데이터를 자바스크립트로 전달 (업로드 후 처리된 데이터)
        const csvProducts = <?php echo isset($csvData) ? json_encode($csvData) : '[]'; ?>;

        // 장바구니에 상품을 추가하는 함수
        function addToCart(product) {
            const existingItem = cart.find(item => item.number === product.number);

            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                product.quantity = 1;
                cart.push(product);
            }

            renderCart();
        }

        // 상품의 수량을 변경하는 함수
        function changeQuantity(productNumber, amount) {
            const item = cart.find(item => item.number === productNumber);

            if (item) {
                item.quantity += amount;
                if (item.quantity <= 0) {
                    cart = cart.filter(item => item.number !== productNumber);
                }
            }

            renderCart();
        }

        // 장바구니 렌더링
        function renderCart() {
            const cartItemsContainer = document.getElementById('cart-items');
            cartItemsContainer.innerHTML = '';

            let total = 0;

            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;

                const cartItem = document.createElement('div');
                cartItem.className = 'cart-item';

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

                cartItemsContainer.appendChild(cartItem);
            });

            document.getElementById('cart-total').textContent = total;
        }

        // 결제하기 버튼
        function checkout() {
            alert(`총 결제 금액은 ${document.getElementById('cart-total').textContent} 원입니다.`);
        }

        // CSV로 업로드된 상품을 장바구니에 추가
        csvProducts.forEach(product => {
            addToCart(product);
        });

    </script>
</body>
</html>
