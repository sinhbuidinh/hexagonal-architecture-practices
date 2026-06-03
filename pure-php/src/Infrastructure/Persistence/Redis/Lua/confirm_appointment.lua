-- KEYS[1] appointment hash key
-- Returns 1 on success, 0 if appointment missing
if redis.call('EXISTS', KEYS[1]) == 0 then
    return 0
end
redis.call('DEL', KEYS[1])
return 1
