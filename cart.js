// JavaScript to handle adding packages to the cart

// Function to add a package to the cart
function addToCart(packageName) {
    // Retrieve the cart from localStorage or initialize it as an empty array
    let cart = JSON.parse(localStorage.getItem('cart')) || [];

    // Check if the package already exists in the cart
    const packageIndex = cart.findIndex(item => item.name === packageName);

    if (packageIndex >= 0) {
        // Increment the quantity if the package already exists
        cart[packageIndex].quantity += 1;
    } else {
        // Add a new package to the cart
        cart.push({ name: packageName, quantity: 1 });
    }

    // Save the updated cart back to localStorage
    localStorage.setItem('cart', JSON.stringify(cart));

    alert(`${packageName} has been added to the cart!`);
}

// Function to render the cart items on the cart page
function renderCart() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const orderList = document.querySelector('.order-list');

    // Clear existing items
    orderList.innerHTML = '';

    // Loop through cart items and display them
    cart.forEach(item => {
        const orderItem = document.createElement('div');
        orderItem.classList.add('order-item');

        orderItem.innerHTML = `
            <span>${item.name}</span>
            <span>${item.quantity}</span>
            <span><button class="remove-btn" onclick="removeFromCart('${item.name}')">Remove</button></span>
        `;

        orderList.appendChild(orderItem);
    });

    // Update the total amount
    document.getElementById('total-amount').innerText = `$${calculateTotal(cart)}`;
}

// Function to remove a package from the cart
function removeFromCart(packageName) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];

    // Filter out the package to remove
    cart = cart.filter(item => item.name !== packageName);

    // Save the updated cart back to localStorage
    localStorage.setItem('cart', JSON.stringify(cart));

    // Re-render the cart
    renderCart();
}

// Function to calculate the total amount (assuming fixed prices for demo purposes)
function calculateTotal(cart) {
    const prices = {
        'Package A': 10.00,
        'Package B': 15.00,
        'Package C': 20.00
    };

    return cart.reduce((total, item) => total + (prices[item.name] * item.quantity), 0).toFixed(2);
}

// Attach event listeners to package buttons on the Manage Order page
if (document.querySelectorAll('.package button')) {
    document.querySelectorAll('.package button').forEach(button => {
        button.addEventListener('click', () => {
            const packageName = button.parentElement.querySelector('h3').innerText;
            addToCart(packageName);
        });
    });
}

// Render the cart if on the cart page
if (document.querySelector('.cart-page')) {
    renderCart();
}
