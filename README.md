# CashWorx API

CashWorx API provides endpoints to manage user onboarding, authentication, activity settings, payments, and preferences for the CashWorx app. This document outlines the project setup, available endpoints, and expected responses.

---

## **Project Setup**

### Prerequisites

-   PHP 8.1 or higher
-   Laravel 10.x
-   MySQL 8.x or PostgreSQL
-   Composer 2.x
-   Node.js and NPM (for optional front-end dependencies)

### Installation

1. Clone the repository:

    ```bash
    git clone https://github.com/your-repo/cashworx-api.git
    cd cashworx-api
    ```

2. Install dependencies:

    ```bash
    composer install
    npm install
    ```

3. Configure the environment:

    - Copy `.env.example` to `.env`:
        ```bash
        cp .env.example .env
        ```
    - Update `.env` with database credentials and other necessary configuration.

4. Generate the application key:

    ```bash
    php artisan key:generate
    ```

5. Run migrations and seed the database:

    ```bash
    php artisan migrate --seed
    ```

6. Start the local development server:
    ```bash
    php artisan serve
    ```

### Testing the API

-   Use tools like [Postman](https://www.postman.com) or [Thunder Client](https://marketplace.visualstudio.com/items?itemName=rangav.vscode-thunder-client) to test the endpoints.
-   Run automated tests:
    ```bash
    php artisan test
    ```

---

## **Base URL**

All endpoints are prefixed with the base URL:

```
localhost:8000/api
```

`OR`

```
domain.com/api
```

---

## **Endpoints**

### **Onboarding**

-   **GET** `/onboarding`: Fetch onboarding data.
-   **PATCH** `/onboarding/:id`: Update a specific onboarding screen.

### **Authentication**

-   **POST** `/auth/register`: Register a new user.
-   **POST** `/auth/otp/send`: Send OTP for verification.
-   **POST** `/auth/otp/verify`: Verify OTP.
-   **POST** `/auth/login`: Authenticate user login.
-   **POST** `/auth/oauth`: Login using OAuth providers.
-   **POST** `/auth/forgot-password`: Initiate password reset.
-   **POST** `/auth/reset-password`: Reset user password.
-   **POST** `/auth/verify/:phone_number`: Verify a user's phone number.

### **User**

-   **GET** `/user/greeting`: Fetch a personalized greeting.
-   **GET** `/user/activity`: Fetch user activity settings.
-   **GET** `/user/activity/tax`: Fetch tax activity settings.
-   **POST** `/user/activity/tax`: Update tax activity settings.
-   **GET** `/user/activity/tax/:id`: Fetch details of a specific tax activity.
-   **PATCH** `/user/activity/tax/:id`: Update a specific tax activity.
-   **GET** `/user/activity/tax/history`: Fetch tax payment history.
-   **POST** `/user/activity/tax/calculate`: Calculate tax.

### **Payments**

-   **GET** `/platforms/payment/options`: List payment options.
-   **POST** `/platforms/payment/tax`: Process tax payments.
-   **POST** `/platforms/payment/fees`: Process fee payments.

### **Settings**

-   **POST** `/settings/languages`: Set user language preferences.
-   **GET** `/settings/states`: Get available states/regions.

---

## **Error Codes**

-   `400 Bad Request`: Malformed or missing parameters.
-   `401 Unauthorized`: Invalid or missing API key.
-   `404 Not Found`: Resource not found.
-   `500 Internal Server Error`: Unexpected server error.

---

## **Contributing**

1. Fork the repository.
2. Create a feature branch.
3. Commit your changes.
4. Push to the branch.
5. Open a pull request.

---

## **License**

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

---

## **Contact**

For questions or support, contact [redemptionjonathan@outlook.com](mailto:redemptionjonathan@outlook.com).

---
