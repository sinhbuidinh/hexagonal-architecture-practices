-- KEYS[1] slots key, KEYS[2] appointment hash key
-- ARGV[1] slots, ARGV[2] practitioner_id, ARGV[3] patient_id, ARGV[4] expires_at (ISO-8601)
local available = tonumber(redis.call('GET', KEYS[1]) or '0')
local requested = tonumber(ARGV[1])
if available < requested then
    return {0, available}
end
redis.call('DECRBY', KEYS[1], requested)
redis.call('HSET', KEYS[2],
    'practitioner_id', ARGV[2],
    'patient_id', ARGV[3],
    'slots', ARGV[1],
    'expires_at', ARGV[4]
)
return {1, available - requested}
