import { FibPay } from 'fibpay';
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
dotenv.config({ path: path.resolve(__dirname, '../.env') });

const fib = new FibPay({
  clientId: process.env.FIB_CLIENT_ID,
  clientSecret: process.env.FIB_CLIENT_SECRET,
  environment: process.env.FIB_ENVIRONMENT || 'stage',
});

const action = process.argv[2];

async function run() {
  try {
    if (action === 'create') {
      const amount = parseInt(process.argv[3]);
      const currency = process.argv[4] || 'IQD';
      const description = process.argv[5] || 'Order';
      const callbackUrl = process.argv[6] !== 'null' ? process.argv[6] : undefined;
      const redirectUrl = process.argv[7] !== 'null' ? process.argv[7] : undefined;

      const payment = await fib.createPayment({
        amount,
        currency,
        description,
        callbackUrl,
        redirectUrl
      });
      console.log('---FIB-JSON-START---' + JSON.stringify(payment) + '---FIB-JSON-END---');
    } else if (action === 'status') {
      const paymentId = process.argv[3];
      const status = await fib.getStatus(paymentId);
      console.log('---FIB-JSON-START---' + JSON.stringify(status) + '---FIB-JSON-END---');
    } else {
      console.error('---FIB-JSON-START---' + JSON.stringify({ error: 'Invalid action' }) + '---FIB-JSON-END---');
      process.exit(1);
    }
  } catch (err) {
    console.error('---FIB-JSON-START---' + JSON.stringify({ 
      error: err.message, 
      statusCode: err.statusCode,
      body: err.body 
    }) + '---FIB-JSON-END---');
    process.exit(1);
  }
}

run();
