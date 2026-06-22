# Custom WooCommerce Dining Set Options

This plugin adds a simple dining set configurator to normal WooCommerce products. It is designed for stores that sell dining table sets where customers choose a table color, chair color, and extra chairs.

It is intentionally simple: the store admin manages products the normal WooCommerce way with attributes, variations, prices, and images. There is no required JSON editing and no external SaaS dependency.

---

## What the plugin does

On a dining set product page, the plugin can show:

- Table Color swatches
- Chair Color swatches
- Extra Chairs selector
- A product preview image
- Dynamic extra-chair price update

When the customer adds the product to cart, the selected table color, chair color, and extra chairs are saved with the cart item and later with the WooCommerce order.

---

## WooCommerce requirement

WooCommerce must be installed and active.

The recommended product type is a normal **Variable Product** because the client can use standard WooCommerce attributes and variations.

---

## Installation and activation

1. Upload the plugin folder to `wp-content/plugins/custom-woocommerce-product-configurator/`.
2. Go to **WordPress Admin → Plugins → Installed Plugins**.
3. Find **Custom WooCommerce Product Configurator**.
4. Click **Activate**.
5. Confirm WooCommerce is active.

---

## Recommended product setup

Create dining sets as normal WooCommerce variable products.

1. Go to **Products → Add New** or edit an existing dining set product.
2. Set **Product data** to **Variable product**.
3. Add attributes such as:
   - `Table Color`
   - `Chair Color`
   - `Chair Quantity` or `Extra Chairs`
   - `Table Size` if needed
4. Check **Used for variations** for attributes that should create variations.
5. Create variations normally.
6. Set normal WooCommerce prices and images as needed.

The plugin reads the product attributes and provides a friendlier dining set selection UI on the product page.

---

## Dining Set Options tab

After activation, edit a WooCommerce product and go to:

**Product → Edit Product → Product Data → Dining Set Options**

Fields in this tab:

1. **Enable Dining Set Configurator**  
   Turns the dining set UI on for this product.

2. **Base set includes number of chairs**  
   Default is `6`. Example: a table set includes 6 chairs before any extras.

3. **Extra chair price**  
   Price added for each extra chair selected by the customer.

4. **Sync chair color with table color by default**  
   If enabled, choosing a table color automatically chooses the same chair color.

5. **Hide Chair Color field unless “Different chair color” is selected**  
   Keeps the frontend simpler unless the customer wants a different chair color.

6. **Product preview image mapping**  
   Add rows that connect:
   - Table color
   - Chair color
   - Preview image

Use the same color names as the WooCommerce attributes, for example `Oak`, `Black`, `Walnut`, or `White`.

---

## Client workflow: create a dining set product

1. Go to **Products → Add New**.
2. Add product title, description, main image, and gallery.
3. Set **Product data** to **Variable product**.
4. Add product attributes such as **Table Color** and **Chair Color**.
5. Create variations normally.
6. Open **Product Data → Dining Set Options**.
7. Check **Enable Dining Set Configurator**.
8. Set **Base set includes number of chairs**. Usually `6`.
9. Set **Extra chair price**.
10. Choose whether chair color should sync with table color.
11. Add preview image mapping rows.
12. Click **Update**.
13. Open the product page and test the frontend selector.

---

## Frontend customer flow

On the product page, the customer can:

1. Choose a table color.
2. Choose a chair color, or keep it synced with the table color.
3. Select extra chairs.
4. See the preview image update.
5. See the extra-chair price update.
6. Add the configured product to cart.

The normal WooCommerce variation form still exists, so product attributes and variations remain close to standard WooCommerce behavior.

---

## Cart, checkout, and order data

When the product is added to cart, the plugin stores the dining set choices in cart item data:

- Table Color
- Chair Color
- Extra Chairs
- Extra-chair price adjustment
- Preview image URL when available

At checkout, this data remains attached to the line item. After purchase, it is saved as WooCommerce order item meta.

---

## How admin views customization in an order

1. Go to **WooCommerce → Orders**.
2. Open the order.
3. Find the dining set product line item.
4. Review the item meta/details.
5. You should see the selected table color, chair color, extra chairs, and preview link if available.

---

## Shortcode fallback

The old shortcode renderer still exists for compatibility:

```text
[cwpc_configurator product_id="123"]
```

For the simplified dining set flow, the recommended setup is the normal product page with **Product Data → Dining Set Options** enabled.

---

## Troubleshooting

### Dining set options do not show on the product page

Check:

1. WooCommerce is active.
2. Product is published.
3. Product is a WooCommerce product, preferably a variable product.
4. Product has attributes such as `Table Color` and `Chair Color`.
5. Go to **Product Data → Dining Set Options**.
6. Confirm **Enable Dining Set Configurator** is checked.
7. Click **Update**.
8. Clear cache and reload the product page.

### Swatches are empty

The plugin reads product attributes. Make sure the product has attributes named clearly, for example:

- Table Color
- Chair Color

Also make sure the attributes have values.

### Preview image does not change

Check the **Product preview image mapping** rows:

1. Table color should match the attribute value.
2. Chair color should match the attribute value.
3. Preview image should be selected from the Media Library.
4. Update the product after changing mappings.

### Extra chair price does not update

Check **Extra chair price** in **Product Data → Dining Set Options**. Then test with a fresh cart.

---

## Screenshots placeholder section

Add screenshots after installing on the client site:

1. **Screenshot 1:** Variable product attributes for Table Color and Chair Color
2. **Screenshot 2:** Product Data → Dining Set Options tab
3. **Screenshot 3:** Preview image mapping rows
4. **Screenshot 4:** Frontend table/chair color swatches
5. **Screenshot 5:** WooCommerce order item meta with selected dining set options

---

## Notes for client/store admin

- Use normal WooCommerce attributes and variations.
- Keep color names consistent between attributes and preview mapping rows.
- Start with one dining set product and test the full purchase flow.
- Avoid editing advanced/developer configurator data unless a developer asks you to.
