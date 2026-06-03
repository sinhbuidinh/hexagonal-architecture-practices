-- KEYS[1] prescription hash
-- ARGV[1] expected_version
-- ARGV[2..8] medication, dosage, instructions, status, pharmacy_notes, last_updated_by
local current = redis.call('HGET', KEYS[1], 'version')
if current == false then
    return {0, -1}
end
if tonumber(current) ~= tonumber(ARGV[1]) then
    return {0, tonumber(current)}
end
local new_version = tonumber(current) + 1
redis.call('HSET', KEYS[1],
    'medication', ARGV[2],
    'dosage', ARGV[3],
    'instructions', ARGV[4],
    'status', ARGV[5],
    'pharmacy_notes', ARGV[6],
    'last_updated_by', ARGV[7],
    'version', tostring(new_version)
)
return {1, new_version}
