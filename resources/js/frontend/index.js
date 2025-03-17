
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

const label = decodeEntities( settings.title ) || defaultLabel;
/**
 * Content component
 */
const Content = () => {
	 return (
		<div>
			{decodeEntities(settings.description) || __('Pay with FIB using secure methods', 'fib-payments-gateway')}
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
