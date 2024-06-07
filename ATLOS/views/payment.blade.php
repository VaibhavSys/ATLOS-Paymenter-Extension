<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ATLOS Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            background-color: #5918b6;
            overflow: hidden;
        }

        .card {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 20px;
            font-family: 'Arial', sans-serif;
        }

        .btn {
            margin-top: 10px;
        }

        .modal-content {
            background-color: #333;
            color: #fff;
        }
    </style>
</head>
<body>
    <div id="particles-js"></div>
    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="card mx-auto rounded border-0" style="max-width: 400px;">
            <div class="card-body text-center text-white">
                <h5 class="card-title">ATLOS Payment</h5>
                <h6 class="card-subtitle mb-2">Order ID: {{ $order_id }}</h6>
                <p class="card-text">Complete your payment using ATLOS.</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-success mt-10" type="button" onclick="pay()">Pay</button>
                    <button class="btn btn-danger mt-10" type="button" onclick="handleCanceled()">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Payment Completed</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="redirectSuccess()"></button>
                </div>
                <div class="modal-body">
                    Your payment has been completed and is awaiting confirmation from the blockchain. Crypto payments may take some time depending on the blockchain, so please be patient.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="redirectSuccess()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelModalLabel">Payment Canceled</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="redirectCancel()"></button>
                </div>
                <div class="modal-body">
                    Your payment has been canceled. If this was a mistake, please try again.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="redirectCancel()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script async src="https://atlos.io/packages/app/atlos.js"></script>
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>

<script>
    particlesJS('particles-js', {
    "particles": {
        "number": {
        "value": 80,
        "density": {
            "enable": true,
            "value_area": 800
        }
        },
        "color": {
        "value": "#ffffff"
        },
        "shape": {
        "type": "circle",
        "stroke": {
            "width": 0,
            "color": "#000000"
        },
        "polygon": {
            "nb_sides": 5
        }
        },
        "opacity": {
        "value": 0.5,
        "random": false,
        "anim": {
            "enable": false,
            "speed": 1,
            "opacity_min": 0.1,
            "sync": false
        }
        },
        "size": {
        "value": 3,
        "random": true,
        "anim": {
            "enable": false,
            "speed": 40,
            "size_min": 0.1,
            "sync": false
        }
        },
        "line_linked": {
        "enable": true,
        "distance": 150,
        "color": "#ffffff",
        "opacity": 0.4,
        "width": 1
        },
        "move": {
        "enable": true,
        "speed": 6,
        "direction": "none",
        "random": false,
        "straight": false,
        "out_mode": "out",
        "bounce": false,
        "attract": {
            "enable": false,
            "rotateX": 600,
            "rotateY": 1200
        }
        }
    },
    "interactivity": {
        "detect_on": "canvas",
        "events": {
        "onhover": {
            "enable": true,
            "mode": "repulse"
        },
        "onclick": {
            "enable": true,
            "mode": "push"
        },
        "resize": true
        },
        "modes": {
        "grab": {
            "distance": 400,
            "line_linked": {
            "opacity": 1
            }
        },
        "bubble": {
            "distance": 400,
            "size": 40,
            "duration": 2,
            "opacity": 8,
            "speed": 3
        },
        "repulse": {
            "distance": 200,
            "duration": 0.4
        },
        "push": {
            "particles_nb": 4
        },
        "remove": {
            "particles_nb": 2
        }
        }
    },
    "retina_detect": true
    });

    const merchantId = "{{ $merchant_id }}"
    const orderId = "{{ $order_id }}"
    const total = Number("{{ $total }}")
    const currency = "{{ $currency }}"
    const conversionRate = Number("{{ $conversion_rate }}")
    const theme = "{{ $theme }}"
    const orderCurrency = conversionRate === -1 ? currency : 'USD';

    async function handleSuccess() {
        const modalEl = document.getElementById('paymentModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    async function handleCanceled() {
        const modalEl = document.getElementById('cancelModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    function redirectSuccess() {
        window.location.href = `/extensions/atlos/payment/success/${orderId}`
    }

    function redirectCancel() {
        window.location.href = `/extensions/atlos/payment/cancel/${orderId}`
    }

    function pay() {
        atlos.Pay({
            merchantId: merchantId,
            orderId: orderId, 
            orderAmount: total, 
            orderCurrency: orderCurrency,
            onSuccess: handleSuccess,
            theme: theme
        });
    }
</script>
