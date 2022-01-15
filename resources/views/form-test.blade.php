<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('components/css/bootstrap4.css')}}" />
    <title>Document</title>
</head>
<body>
    <div class="container">
        <div class="d-flex mt-3">
            <input type="text" id="get-cities-input" class="form-control" placeholder="province id" />
            <button id="get-cities-btn" class="btn">get cities</button>
        </div>
        <div class="d-flex mt-3">
            <input type="text" id="get-service-price-city-input" class="form-control" placeholder="city id"/>
            <input type="text" id="get-service-price-weight-input" class="form-control" placeholder="weight"/>
            <button id="get-service-price-btn" class="btn">get price</button>
        </div>
        <div class="d-flex mt-3">
            <input type="text" id="get-status-service-input" class="form-control" placeholder="service id" />
            <input type="text" id="get-status-tracking-code-input" class="form-control" placeholder="tracking code" />
            <button id="get-status-btn" class="btn">get status</button>
        </div>
        <div class="d-flex mt-3">
            <input type="text" id="cancel-delivery-service-input" class="form-control" placeholder="service id"/>
            <input type="text" id="cancel-delivery-tracking-code-input" class="form-control" placeholder="tracking code"/>
            <button id="cancel-delivery-btn" class="btn">cancel delivery</button>
        </div>
        <div class="d-flex mt-3">
            <input type="text" id="add-delivery-service-input" class="form-controller" placeholder="service id"/>
            <input type="text" id="add-delivery-city-input" class="form-control" placeholder="city id"/>
            <input type="text" id="add-delivery-company-tracking-code-input" class="form-control" placeholder="company tracking code"/>
            <input type="text" id="add-delivery-address-input" class="form-control" placeholder="address"/>
            <input type="text" id="add-delivery-full-name-input" class="form-control" placeholder="full name"/>
            <input type="text" id="add-delivery-delivery-type-input" class="form-control" placeholder="delivery type"/>
            <input type="text" id="add-delivery-shift-input" class="form-control" placeholder="delivery shift"/>
            <input type="text" id="add-delivery-parcel-type-input" class="form-control" placeholder="parcel type"/>
            <input type="text" id="add-delivery-send-date-input" class="form-control" placeholder="send date"/>
            <button id="add-delivery-btn" class="btn">add delivery</button>
        </div>
        <textarea id="response-text-area" class="col-12 mt-3 form-control" style="height: 400px" spellcheck="false" placeholder="api response ..."></textarea>
    </div>
</body>
<script src="{{ asset('components/js/jquery.js')}}"></script>
<script src="{{ asset('components/js/bootstrap.js')}}"></script>
<script>
    
    let getCitiesButton = document.getElementById('get-cities-btn');
    let getServicePriceButton = document.getElementById('get-service-price-btn');
    let getStatusButton = document.getElementById('get-status-btn');
    let cancelDeliveryButton = document.getElementById('cancel-delivery-btn');
    let addDeliveryButton = document.getElementById('add-delivery-btn');
    let ResponseTextArea = document.getElementById('response-text-area');

    getCitiesButton.addEventListener('click', function(){
        let getCitiesInput = document.getElementById('get-cities-input');
        $.ajax({
            method: 'POST',
            url: '/api/get-cities',
            data: {
                provinceId: getCitiesInput.value,
                _token: "{{ csrf_token() }}",
            },
            success: function(response) {
                ResponseTextArea.value = response;
            }   
        });
    });

    getServicePriceButton.addEventListener('click', function(){
        let getServicePriceCityId = document.getElementById('get-service-price-city-input');
        let getServicePriceWeight = document.getElementById('get-service-price-weight-input');
        $.ajax({
            method: 'POST',
            url: '/api/get-service-and-price',
            data: {
                cityId: getServicePriceCityId.value,
                weight: getServicePriceWeight.value,
                _token: "{{ csrf_token() }}",
            },
            success: function(response) {
                ResponseTextArea.value = response;
            }   
        });
    });

    getStatusButton.addEventListener('click', function(){
        let getStatusServiceId = document.getElementById('get-status-service-input');
        let getStatusTrackingCode = document.getElementById('get-status-tracking-code-input');
        $.ajax({
            method: 'POST',
            url: '/api/get-delivery-status',
            data: {
                serviceId: getStatusServiceId.value,
                trackingCode: getStatusTrackingCode.value,
                _token: "{{ csrf_token() }}",
            },
            success: function(response) {
                ResponseTextArea.value = response;
            }   
        });
    });

    cancelDeliveryButton.addEventListener('click', function(){
        let cancelDeliveryServiceId = document.getElementById('cancel-delivery-service-input');
        let cancelDeliverytrackingCode = document.getElementById('cancel-delivery-tracking-code-input');
        $.ajax({
            method: 'POST',
            url: '/api/cancel-delivery',
            data: {
                serviceId: cancelDeliveryServiceId.value,
                trackingCode: cancelDeliverytrackingCode.value,
                _token: "{{ csrf_token() }}",
            },
            success: function(response) {
                ResponseTextArea.value = response;
            }   
        });
    });

    addDeliveryButton.addEventListener('click', function(){
        let serviceIdInput = document.getElementById('add-delivery-service-input');
        let cityIdInput = document.getElementById('add-delivery-city-input');
        let companyTrackingCodeInput = document.getElementById('add-delivery-company-tracking-code-input');
        let addressInput = document.getElementById('add-delivery-address-input');
        let fullNameInput = document.getElementById('add-delivery-full-name-input');
        let deliveryTypeInput = document.getElementById('add-delivery-delivery-type-input');
        let shiftInput = document.getElementById('add-delivery-shift-input');
        let parcelTypeInput = document.getElementById('add-delivery-parcel-type-input');
        let sendDateInput = document.getElementById('add-delivery-send-date-input');
        $.ajax({
            method: 'POST',
            url: '/api/add-delivery',
            data: {
                serviceId: serviceIdInput.value,
                cityId: cityIdInput.value,
                companyTrackingCode: companyTrackingCodeInput.value,
                address: addressInput.value,
                fullName: fullNameInput.value,
                deliveryType: deliveryTypeInput.value,
                shift: shiftInput.value,
                parcelType: parcelTypeInput.value,
                sendDate: sendDateInput.value,
                _token: "{{ csrf_token() }}",
            },
            success: function(response) {
                ResponseTextArea.value = response;
            }   
        });
    });

</script>
</html>