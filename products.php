<?php include 'includes/header.php'; ?>
<?php
require_once 'config/db.php';

// Handle Add/Edit Product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_product'])) {
    $id = $_POST['product_id'] ?? '';
    $code = $_POST['code'];
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $size = $_POST['size'];
    $color = $_POST['color'];
    $gender = $_POST['gender'];
    $cost_price = $_POST['cost_price'];
    $sell_price = $_POST['sell_price'];
    $stock = $_POST['stock'];

    // Image Upload
    $image = $_POST['current_image'] ?? '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/";
        $image_name = time() . '_' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image = $image_name;
        }
    }

    try {
        if ($id) {
            // Update
            $sql = "UPDATE products SET code=?, name=?, category_id=?, size=?, color=?, gender=?, cost_price=?, sell_price=?, stock=?, image=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$code, $name, $category_id, $size, $color, $gender, $cost_price, $sell_price, $stock, $image, $id]);
            $msg = "Produto atualizado com sucesso!";
        } else {
            // Insert
            $sql = "INSERT INTO products (code, name, category_id, size, color, gender, cost_price, sell_price, stock, image) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$code, $name, $category_id, $size, $color, $gender, $cost_price, $sell_price, $stock, $image]);
            $msg = "Produto cadastrado com sucesso!";
        }
        echo "<script>Swal.fire('Sucesso!', '$msg', 'success').then(() => { window.location.href='products.php'; });</script>";
    } catch (PDOException $e) {
        echo "<script>Swal.fire('Erro!', 'Erro ao salvar: " . $e->getMessage() . "', 'error');</script>";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    echo "<script>window.location.href='products.php';</script>";
}

// Pagination & Search
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? $_GET['search'] : '';

$where = "";
$params = [];
if ($search) {
    $search_lower = mb_strtolower($search, 'UTF-8');
    $where = "WHERE LOWER(p.name) LIKE ? OR LOWER(p.code) LIKE ?";
    $params = ["%$search_lower%", "%$search_lower%"];
}

// Count total
$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM products p $where");
$total_stmt->execute($params);
$total_products = $total_stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

// Fetch products
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        $where 
        ORDER BY p.id DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories with size_type
$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-4">
            <h2 class="fw-bold text-white">Produtos</h2>
        </div>
        <div class="col-md-4">
             <form method="GET" class="d-flex">
                <input type="text" name="search" class="form-control me-2" placeholder="Buscar por Nome ou Código..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                <?php if($search): ?>
                    <a href="products.php" class="btn btn-secondary ms-2"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" onclick="openModal()">
                <i class="fas fa-plus me-2"></i> Novo Produto
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Imagem</th>
                            <th>Código</th>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Preço Venda</th>
                            <th>Estoque</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $prod): ?>
                            <tr>
                                <td>
                                    <?php if ($prod['image']): ?>
                                        <img src="uploads/<?php echo $prod['image']; ?>" width="50" height="50"
                                            class="rounded object-fit-cover">
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Sem img</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $prod['code']; ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo $prod['name']; ?></div>
                                    <small class="text-muted"><?php echo $prod['size']; ?> | <?php echo $prod['color']; ?> |
                                        <?php echo $prod['gender']; ?></small>
                                </td>
                                <td><?php echo $prod['category_name']; ?></td>
                                <td class="text-success fw-bold">R$
                                    <?php echo number_format($prod['sell_price'], 2, ',', '.'); ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $prod['stock'] < 5 ? 'bg-danger' : 'bg-success'; ?>">
                                        <?php echo $prod['stock']; ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-warning me-1" 
                                            onclick="editProduct(<?php echo $prod['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="products.php?delete=<?php echo $prod['id']; ?>" class="btn btn-sm btn-danger"
                                        onclick="return confirm('Tem certeza?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-3">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Anterior</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Próximo</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add/Edit Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="modalTitle">Novo Produto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="productForm">
                <input type="hidden" name="product_id" id="product_id">
                <input type="hidden" name="current_image" id="current_image">
                
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Código (Barras/SKU)</label>
                            <input type="text" name="code" id="code" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nome do Produto</label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Categoria</label>
                            <select name="category_id" id="category_id" class="form-select bg-dark text-white border-secondary" required onchange="updateSizeInput()">
                                <option value="">Selecione...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" data-size-type="<?php echo $cat['size_type'] ?? 'text'; ?>">
                                        <?php echo $cat['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tamanho</label>
                            <div id="size_container">
                                <!-- Default to Text Select -->
                                <select name="size" id="size_select" class="form-select bg-dark text-white border-secondary">
                                    <option value="P">P</option>
                                    <option value="M">M</option>
                                    <option value="G">G</option>
                                    <option value="GG">GG</option>
                                    <option value="XG">XG</option>
                                    <option value="Unico">Único</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Gênero</label>
                            <select name="gender" id="gender" class="form-select bg-dark text-white border-secondary">
                                <option value="Male">Masculino</option>
                                <option value="Female">Feminino</option>
                                <option value="Unisex">Unissex</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cor</label>
                            <input type="text" name="color" id="color" class="form-control">
                        </div>

                        <!-- New Price/Margin Inputs -->
                        <div class="col-md-3">
                            <label class="form-label">Preço Custo</label>
                            <input type="number" step="0.01" name="cost_price" id="cost_price" class="form-control"
                                required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Margem Desejada (%)</label>
                            <input type="number" step="0.01" id="desired_margin" class="form-control"
                                placeholder="Ex: 100" value="100">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Preço Venda</label>
                            <input type="number" step="0.01" name="sell_price" id="sell_price" class="form-control"
                                required>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <small class="text-muted" id="margin-preview"></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Estoque Inicial</label>
                            <input type="number" name="stock" id="stock" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Imagem</label>
                            <input type="file" name="image" class="form-control">
                            <small class="text-muted" id="current_image_text"></small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="save_product" class="btn btn-primary">Salvar Produto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let modalInstance = null;

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize modal safely
        const modalEl = document.getElementById('addProductModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            modalInstance = new bootstrap.Modal(modalEl);
        } else {
            console.error('Bootstrap Modal not initialized. Check if Bootstrap JS is loaded.');
        }

        // Attach event listeners
        const costInput = document.getElementById('cost_price');
        const marginInput = document.getElementById('desired_margin');
        const sellInput = document.getElementById('sell_price');

        if(costInput) costInput.addEventListener('input', calculatePrice);
        if(marginInput) marginInput.addEventListener('input', calculatePrice);
        if(sellInput) sellInput.addEventListener('input', calculateMargin);
    });

    function openModal() {
        if (!modalInstance) {
            alert('Erro: Modal não inicializado. Recarregue a página.');
            return;
        }
        document.getElementById('productForm').reset();
        document.getElementById('product_id').value = '';
        document.getElementById('modalTitle').innerText = 'Novo Produto';
        document.getElementById('current_image_text').innerText = '';
        document.getElementById('margin-preview').innerHTML = '';
        updateSizeInput(); // Reset size input
        modalInstance.show();
    }

    function editProduct(id) {
        if (!modalInstance) {
            alert('Erro: Modal não inicializado.');
            return;
        }

        // Show loading state or similar if desired
        
        // Fetch product data via AJAX
        fetch(`ajax_get_product.php?id=${id}`)
            .then(response => response.json())
            .then(product => {
                if(product.error) {
                    alert('Erro ao carregar produto: ' + product.error);
                    return;
                }

                document.getElementById('product_id').value = product.id;
                document.getElementById('code').value = product.code;
                document.getElementById('name').value = product.name;
                document.getElementById('category_id').value = product.category_id;
                document.getElementById('color').value = product.color;
                document.getElementById('gender').value = product.gender;
                document.getElementById('cost_price').value = product.cost_price;
                document.getElementById('sell_price').value = product.sell_price;
                document.getElementById('stock').value = product.stock;
                document.getElementById('current_image').value = product.image;
                
                if(product.image) {
                    document.getElementById('current_image_text').innerText = 'Imagem atual: ' + product.image;
                } else {
                    document.getElementById('current_image_text').innerText = '';
                }

                document.getElementById('modalTitle').innerText = 'Editar Produto';
                
                // Trigger margin calculation
                calculateMargin();
                
                // Handle Size Input
                updateSizeInput(product.size);

                modalInstance.show();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao buscar dados do produto.');
            });
    }

    function updateSizeInput(currentSize = null) {
        const categorySelect = document.getElementById('category_id');
        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        const sizeType = selectedOption ? selectedOption.getAttribute('data-size-type') : 'text';
        const sizeContainer = document.getElementById('size_container');

        let html = '';
        if (sizeType === 'number') {
            html = `<input type="number" name="size" id="size_input" class="form-control" placeholder="Ex: 38" required>`;
        } else {
            html = `
                <select name="size" id="size_select" class="form-select bg-dark text-white border-secondary">
                    <option value="P">P</option>
                    <option value="M">M</option>
                    <option value="G">G</option>
                    <option value="GG">GG</option>
                    <option value="XG">XG</option>
                    <option value="Unico">Único</option>
                </select>
            `;
        }
        sizeContainer.innerHTML = html;

        // Set value if editing
        if (currentSize) {
            if (sizeType === 'number') {
                const input = document.getElementById('size_input');
                if(input) input.value = currentSize;
            } else {
                const select = document.getElementById('size_select');
                if(select) select.value = currentSize;
            }
        }
    }

    function calculatePrice() {
        let cost = parseFloat(document.getElementById('cost_price').value) || 0;
        let margin = parseFloat(document.getElementById('desired_margin').value) || 0;

        if (cost > 0) {
            let price = cost + (cost * (margin / 100));
            document.getElementById('sell_price').value = price.toFixed(2);
            updatePreview(cost, price);
        }
    }

    function calculateMargin() {
        let cost = parseFloat(document.getElementById('cost_price').value) || 0;
        let price = parseFloat(document.getElementById('sell_price').value) || 0;

        if (cost > 0 && price > 0) {
            let margin = ((price - cost) / cost) * 100;
            document.getElementById('desired_margin').value = margin.toFixed(2);
            updatePreview(cost, price);
        }
    }

    function updatePreview(cost, price) {
        let profit = price - cost;
        let marginPercent = (profit / price) * 100; // Margin on sell price
        let markupPercent = (profit / cost) * 100; // Markup on cost

        let html = `Lucro: R$ ${profit.toFixed(2)}<br>Markup: ${markupPercent.toFixed(1)}%`;
        document.getElementById('margin-preview').innerHTML = html;
    }
</script>

<?php include 'includes/footer.php'; ?>