// src/index.ts

export default {
  async fetch(request: Request): Promise<Response> {
    
    // --- Configuration (same as your PHP script) ---
    const category = 'football';
    
    // Get today's date in YYYY-MM-DD format
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0'); // Months are 0-indexed
    const day = String(today.getDate()).padStart(2, '0');
    const date = `${year}-${month}-${day}`;

    // 1. Construct the full API URL
    const apiUrl = `https://www.sofascore.com/api/v1/sport/${category}/scheduled-events/${date}`;

    // 2. Define the necessary request headers
    const headers = {
      'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36',
      'Accept': '*/*',
      'X-Requested-With': '077dd6',
      'DNT': '1'
    };

    // 3. Execute the request using the fetch API (the JS equivalent of cURL)
    console.log(`Fetching data from: ${apiUrl}`);
    const response = await fetch(apiUrl, { headers: headers });

    // 4. Check if the request was successful
    if (!response.ok) {
      return new Response('Error fetching data from the SofaScore API', { status: response.status });
    }

    // 5. Get the JSON data from the response
    const data = await response.json();

    // 6. Return the data as a JSON response
    // JSON.stringify makes the JSON nicely formatted.
    // We add CORS headers to allow any frontend to call this worker.
    return new Response(JSON.stringify(data, null, 2), {
      headers: {
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*', // Allow any domain to access
      },
    });
  },
};