document.addEventListener('DOMContentLoaded', () => {
  const variantSelect = document.querySelector('#variant');
  const variantId = document.querySelector('#variant_id');
  const productQuantity = document.querySelector('#quantity');
  const stockMessage = document.querySelector('#stock-message');

  const syncVariant = () => {
    if (!variantSelect || !variantId) return;
    const option = variantSelect.options[variantSelect.selectedIndex];
    const stock = Number(option?.dataset.stock || 0);
    variantId.value = variantSelect.value;
    if (productQuantity) productQuantity.max = stock || 1;
    if (stockMessage) stockMessage.textContent = stock ? `Còn ${stock} sản phẩm.` : 'Vui lòng chọn biến thể còn hàng.';
  };
  variantSelect?.addEventListener('change', syncVariant);
  productQuantity?.addEventListener('change', () => {
    const max = Number(productQuantity.max || 99);
    productQuantity.value = Math.max(1, Math.min(max, Number(productQuantity.value) || 1));
  });

  document.querySelectorAll('.nam-qty').forEach((input) => {
    input.addEventListener('change', () => {
      const max = Number(input.max || 99);
      input.value = Math.max(0, Math.min(max, Number(input.value) || 0));
    });
  });
});
