import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const settings = getSetting( 'fib_data', {} );

const defaultLabel = __(
	'FIB Payments',
	'woo-gutenberg-products-block'
);

const qrStyle = {
	width: '300px',
  };

  const customLabelStyle = {
	display: 'flex',
	alignItems: 'center', // This ensures vertical centering
  };

const spanContainerStyle = {
	flex: '1 1 100%', // Takes up 50% of the space
	width: '160px',
  };
  
  const imgContainerStyle = {
	flex: '1 1 50%', // Takes up 50% of the space
  };

  const fetch = require('node-fetch');

// Function to get access token


  

const label = decodeEntities( settings.title ) || defaultLabel;
/**
 * Content component
 */
const Content = () => {
	const qrData = "data:image/png;base64,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";

	// async function getAccessToken() {
	// 	const tokenUrl = 'https://fib.stage.fib.iq/auth/realms/fib-online-shop/protocol/openid-connect/token';
	// 	const params = new URLSearchParams();
	// 	params.append('grant_type', 'client_credentials');
	// 	params.append('client_id', 'fib-client-19');
	// 	params.append('client_secret', '480eb521-900f-4070-b0aa-2289ef144766');
	  
	// 	const options = {
	// 	  method: 'POST',
	// 	  body: params,
	// 	  headers: {
	// 		'Content-Type': 'application/x-www-form-urlencoded',
	// 	  },
	// 	};
	  
	// 	try {
	// 	  const response = await fetch(tokenUrl, options);
	// 	  const data = await response.json();
	// 	  return data.access_token;
	// 	} catch (error) {
	// 	  console.error('Error getting access token:', error);
	// 	  return null;
	// 	}
	//   }
	  
	//   // Function to generate QR code
	//   async function generateQRCode(accessToken) {
	// 	const paymentUrl = 'https://fib.stage.fib.iq/protected/v1/payments';
	  
	// 	const data = {
	// 	  monetaryValue: {
	// 		amount: "500.00",
	// 		currency: "IQD"
	// 	  },
	// 	  statusCallbackUrl: "https://URL_TO_UPDATE_YOUR_PAYMENT_STATUS",
	// 	  description: "Lorem ipsum dolor sit amet."
	// 	};
	  
	// 	const options = {
	// 	  method: 'POST',
	// 	  headers: {
	// 		'Content-Type': 'application/x-www-form-urlencoded',
	// 		'Authorization': `Bearer ${accessToken}`
	// 	  },
	// 	  body: new URLSearchParams(data)
	// 	};
	  
	// 	try {
	// 	  const response = await fetch(paymentUrl, options);
	// 	  const qrCodeData = await response.json();
	// 	  console.log('QR Code Data:', qrCodeData);
	// 	} catch (error) {
	// 	  console.error('Error generating QR code:', error);
	// 	}
	//   }
	  
	//   // Usage: Execute sequentially
	//   async function main() {
	// 	const accessToken = await getAccessToken();
	// 	if (accessToken) {
	// 	  await generateQRCode(accessToken);
	// 	}
	//   }
	  
	//   main();
	
	return (
			<div>
				<img style={qrStyle} src={qrData} alt="QR Code" />
			</div>
	  );
};

const CustomLabelComponent = ({ text, iconSrc }) => {
	
	return (
		<>
		<div className="custom-label" style={customLabelStyle}>
		  <div style={imgContainerStyle}>
			<img src={iconSrc} alt={text} />
			</div>
		  <div style={spanContainerStyle}>
			<span>{text}</span>
			</div>
	  </div>
		</>
	);
  };
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = ( props ) => {
	const text = decodeEntities(settings.title) || defaultLabel;
	const icon = require('../../../assets/images/fi_logo.png'); // Path to the icon
	
	return (
		<CustomLabelComponent text={text} iconSrc={icon} />
	  );
};

/**
 * FIB payment method config object.
 */
const FIB = {
	name: "fib",
	label: <Label />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( FIB );
