[Back](index.md)

# Request Tracing
1. Request trace will apply a Request ID to every HTTP request coming in. Setting it up is simple, just include `\Fuzz\ApiServer\RequestTrace\Middleware\RequestTraceMiddleware` in your middleware stack. It will try to use the ELB header 'X-Amzn-Trace-Id' if it is available otherwise it will generate a UUID.
1. To get the Request ID: `\Fuzz\ApiServer\RequestTrace\Facades\RequestTracer::getRequestId()`