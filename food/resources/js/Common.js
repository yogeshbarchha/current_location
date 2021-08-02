DeliveryAreaType = {};
DeliveryAreaType.Circle = 1;
DeliveryAreaType.Polygon = 2;

DeliveryMode = {};
DeliveryMode.Delivery = 1;
DeliveryMode.Pickup = 2;

PaymentMode = {};
PaymentMode.CashOnDelivery = 1;
PaymentMode.Card = 2;
PaymentMode.WireTransfer = 3;
PaymentMode.Cheque = 4;

EntityStatus = {};
EntityStatus.Disabled = 0;
EntityStatus.Enabled = 1;

String.indexOfAny = function (s, arr, begin) {
  var minIndex = -1;
  for (var i = 0; i < arr.length; i++) {
    var index = s.indexOf(arr[i], begin);
    if (index != -1) {
      if (minIndex == -1 || index < minIndex) {
        minIndex = index;
      }
    }
  }
  return (minIndex);
}

String.splitByAny = function (s, arr) {
  var parts = [];

  var index;
  do {
    index = String.indexOfAny(s, arr);
    if (index != -1) {
      parts.push(s.substr(0, index));
      s = s.substr(index + 1);
    }
    else {
      parts.push(s);
    }
  } while (index != -1);

  return (parts);
}

String.padLeft = function (s, len, padChar) {
  s = String(s);
  if (!padChar) {
    padChar = ' ';
  }
  if (len + 1 < s.length) {
    return ("" + s);
  }
  else {
    return (Array(len + 1 - s.length).join(padChar) + s);
  }
}


function roundToPlaces(num, dec) {
  var result = Math.round(Math.round(num * Math.pow(10, dec + 1)) / Math.pow(10, 1)) / Math.pow(10, dec);
  return result;
}

function getQueryParamValue(key) {
  var query = window.document.location.search.substr(1).split('&');
  var value;
  for (var i = 0; i < query.length; i++) {
    var val = query[i].split('=');
    if (val[0].toLowerCase() == key.toLowerCase()) {
      value = val[1];
    }
  }
  return (value);
}

function getCookie(name) {
  var nameEQ = escape(name) + "=";
  var ca = document.cookie.split(';');
  for (var i = 0; i < ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) === ' ') {
      c = c.substring(1, c.length);
    }
    if (c.indexOf(nameEQ) === 0) {
      return unescape(c.substring(nameEQ.length, c.length));
    }
  }
  return null;
}

function setCookie(name, value, days, path) {
  if (days) {
    var date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    var expires = "; expires=" + date.toGMTString();
  }
  else {
    var expires = "";
  }
  if (!path) {
    path = '/';
  }

  document.cookie = escape(name) + "=" + escape(value) + expires + "; path=" + path;
}

function parseAddress(address) {
  var obj = {
    address: "",
    city: "",
    province: "",
    postalCode: "",
    country: ""
  };

  if (!address) {
    return (obj);
  }

  var parts = address.split(',');
  for (var i = 0; i < parts.length; i++) {
    parts[i] = parts[i].trim();
  }
  var i = parts.length - 1;

  var fnIsPostalCode = function (value) {
    return (/^\d+$/.test(value));
  }

  var fnParsePostalCode = function (value) {
    var subParts = String.splitByAny(value, [' ', '-']);
    for (var j = 0; j < subParts.length; j++) {
      if (fnIsPostalCode(subParts[j].trim())) {
        obj.postalCode = subParts[j].trim();
        if (j > 0) {
          return (subParts[j - 1]);
          break;
        }
      }
    }

    return (value);
  }

  if (i >= 0) {
    if (fnIsPostalCode(parts[i])) {
      obj.postalCode = parts[i];
      i--;
    }
    var part = fnParsePostalCode(parts[i]);
    if (part) {
      obj.country = part;
    }
    i--;
  }

  if (i >= 0) {
    if (fnIsPostalCode(parts[i])) {
      obj.postalCode = parts[i];
      i--;
    }
    var part = fnParsePostalCode(parts[i]);
    if (part) {
      obj.province = part;
    }
    i--;
  }

  if (i >= 0) {
    if (fnIsPostalCode(parts[i])) {
      obj.postalCode = parts[i];
      i--;
    }
    var part = fnParsePostalCode(parts[i]);
    if (part) {
      obj.city = part;
    }
    i--;
  }

  if (i >= 0) {
    parts = parts.slice(0, i + 1);
    obj.address = parts.join(', ');
  }

  return (obj);
}

function concatenateAddressParts(address) {
  var parts = [];

  if (address.address_line1) {
    parts.push(address.address_line1);
  }
  if (address.address_line2) {
    parts.push(address.address_line2);
  }
  if (address.city) {
    parts.push(address.city);
  }
  if (address.state) {
    parts.push(address.state);
  }
  if (address.country) {
    parts.push(address.country);
  }
  if (address.postal_code) {
    parts.push(address.postal_code);
  }

  return (parts.join(', '));
}

function getCurrentLocation(callback) {
  function positionCallback(position) {
    var geocoder = new google.maps.Geocoder();
    geocoder.geocode({'latLng': new google.maps.LatLng(position.coords.latitude, position.coords.longitude)}, function (results, status) {
      if (status == google.maps.GeocoderStatus.OK) {
        if (results[0]) {
          // alert('Address : ' + results[0].formatted_address + ',' + 'Type :
          // ' + results[0].types);
          callback({
            formatted_address: results[0].formatted_address,
            latitude: position.coords.latitude,
            longitude: position.coords.longitude
          });
        }
        else {
          //   alert('Unable to detect your address.');
        }
      }
      else {
        // alert('Unable to detect your address.');
      }
    });
  }

  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(positionCallback);
  }
  else {
    //x.innerHTML = "Geolocation is not supported by this browser.";
  }
}

function getDeliveryGeocompleteOptions() {
  var geocompleteConfig = {
    details: ".details",
    location: "",
    detailsScope: '.location',
    types: ["geocode", "establishment"],
  };
  var delivery_lookup_settings_json = drupalSettings.food.delivery_lookup_settings_json;
  if (delivery_lookup_settings_json) {
    var delivery_lookup_settings = JSON.parse(delivery_lookup_settings_json);
    geocompleteConfig = jQuery.extend(true, geocompleteConfig, delivery_lookup_settings);
  }

  return (geocompleteConfig);
}

function printElement(el) {
  var html = jQuery(el).html();

  var time = new Date().getTime();
  var mywindow = window.open('', 'Printing', 'width=900', 'height=auto');
  mywindow.document.write('<html><head><title>' + document.title + '</title>');
  mywindow.document.write('<link rel="stylesheet" href="/themes/food_theme/css/defaults.css?' + time + '" type="text/css" media="all" />');
  mywindow.document.write('<link rel="stylesheet" href="/themes/food_theme2/css/style.css?' + time + '" type="text/css" media="all" />');
  /*optional stylesheet*/ //mywindow.document.write('<link rel="stylesheet"
                          // href="main.css" type="text/css" />');
  mywindow.document.write('</head><body ><h2>' + document.title + ' - Order Receipt</h2>');
  mywindow.document.write(html);
  mywindow.document.write('</body></html>');

  mywindow.document.close(); // necessary for IE >= 10
  mywindow.focus(); // necessary for IE >= 10

  setTimeout(function () {
    mywindow.print();
    mywindow.close();
  }, 5000);

  return true;
}

jQuery(document).ready(function () {
  jQuery('.delete-button').click(function () {
    if (!confirm('Are you sure you want to delete this item?')) {
      return (false);
    }
  });

  if (jQuery("body").hasClass("page-node-type-news") || jQuery("body").hasClass("page-node-type-event")) {
    jQuery(".carousel-caption .sub-title").text(jQuery(".field__item-title span").text());
  }
  if (jQuery("body").hasClass("page-node-type-article") || jQuery("body").hasClass("page-node-type-page")) {
    jQuery("#block-food-theme2-restaurantsearchform .block-title").text(jQuery(".field__item-title span").text());
  }
  jQuery('.food-order-cancel-button').click(function () {
    if (!confirm('Are you sure you want to cancel this order? Please note this action cannot be undone.')) {
      return (false);
    }
  });
});
