// Minimal Takt ingest stand-in for E2E: records POST /api/event payloads and
// exposes them over GET /__events so specs can assert what the plugin sent.
const http = require('http');

const port = Number(process.env.MOCK_INGEST_PORT || 9911);
const events = [];

const server = http.createServer((req, res) => {
  if (req.method === 'POST' && req.url.startsWith('/api/event')) {
    let body = '';
    req.on('data', (chunk) => {
      body += chunk;
    });
    req.on('end', () => {
      try {
        events.push(JSON.parse(body));
      } catch {
        events.push({ raw: body });
      }
      res.writeHead(202).end();
    });
    return;
  }

  if (req.method === 'GET' && req.url.startsWith('/__events')) {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify(events));
    return;
  }

  if (req.method === 'POST' && req.url.startsWith('/__reset')) {
    events.length = 0;
    res.writeHead(200).end();
    return;
  }

  res.writeHead(404).end();
});

server.listen(port, () => {
  // eslint-disable-next-line no-console
  console.log(`mock ingest on :${port}`);
});
