-- KEYS[1] slots key, KEYS[2] appointment hash key
-- Returns 1 on success, 0 if appointment missing
if redis.call('EXISTS', KEYS[2]) == 0 then
    return 0
end
local slots = tonumber(redis.call('HGET', KEYS[2], 'slots'))
redis.call('INCRBY', KEYS[1], slots)
redis.call('DEL', KEYS[2])
return 1
