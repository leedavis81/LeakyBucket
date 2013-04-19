#LeakyBucket

Used for rate limiting / throttling for a rolling period. Bucket continually empties at a set rate. This is useful to ensure API / Network traffic requesting limiting


## Example

### API Limiting

Update the state of the bucket by loading the last volume of requests within your timeframe, and the time of the last request.
Be sure to update your database (or whatever persistence layer you use) when you've sucessfully performed your rate limited task.

The following example allow for a leaky bucket of 100 requests per minute. 
```
$capacity = 100;         // number of request allowed in the bucket before its full
$timeframe = 60         // the time it would take a full bucket to empty (seconds)

$current_load = {select count(*) from storage where time BETWEEN (now-$timeframe) AND (now)}
$last_request_time = {DateTime object - select last_request_date from storage}

$bucket = System_LeakyBucket::create($capacity, $timeframe)->load($current_load, $last_request_time);

if ($bucket->hasCapacity())
{
    // .. Perform your task ..
    
    // Save an entry into your storage area

    $bucket->fill();
} else
{
    // Service unavailable - try again later
}

```


### Blocking Response (useful for CLI)

You can have the bucket block by passing true into the method ::hasCapacity(true). This will allows you to continually run your process at the throttled rate.
This is useful if you wanted to throttle traffic to another service (eg mail delivery) 
the following example allow for a leaky bucket of 10 requests for every 30 seconds
```
$capacity = 10;         // number of request allowed in the bucket before its full
$timeframe = 30         // the time it would take a full bucket to empty (seconds)

$bucket = System_LeakyBucket::create($capacity, $timeframe);
while($bucket->hasCapacity(true))
{
    // .. Perform your task ..

    $bucket->fill();
}

```