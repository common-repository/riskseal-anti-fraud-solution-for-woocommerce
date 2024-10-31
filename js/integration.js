let client = new rsSDK.RiskSealClient('');

async function riskSealGetCheckID() {
	let s = await client.getRequest('guest', 'checkout');
	document.getElementById('riskseal_sdk_data').value = s;
}

riskSealGetCheckID();