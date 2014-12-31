<?php exit() ?>--by vadash 108.162.254.29
local mod = math.fmod
local floor = math.floor
bit = {}

local function cap(x)
	return mod(x,4294967296)
end

function bit.rshift(x,n)
	return floor(cap(x)/2^n)
end

function bit.bxor(x,y)
	local z,i,j = 0,1
	for j = 0,31 do
		if (mod(x,2)~=mod(y,2)) then
			z = z + i
		end
		x = bit.rshift(x,1)
		y = bit.rshift(y,1)
		i = i*2
	end
	return z
end

function rc4(salt)
	local key = type(salt) == "table" and {table.unpack(salt)} or {string.byte(salt,1,#salt)}
	local S, j, keylength = {[0] = 0,[1] = 1,[2] = 2,[3] = 3,[4] = 4,[5] = 5,[6] = 6,[7] = 7,[8] = 8,[9] = 9,[10] = 10,[11] = 11,[12] = 12,[13] = 13,[14] = 14,[15] = 15,[16] = 16,[17] = 17,[18] = 18,[19] = 19,[20] = 20,[21] = 21,[22] = 22,[23] = 23,[24] = 24,[25] = 25,[26] = 26,[27] = 27,[28] = 28,[29] = 29,[30] = 30,[31] = 31,[32] = 32,[33] = 33,[34] = 34,[35] = 35,[36] = 36,[37] = 37,[38] = 38,[39] = 39,[40] = 40,[41] = 41,[42] = 42,[43] = 43,[44] = 44,[45] = 45,[46] = 46,[47] = 47,[48] = 48,[49] = 49,[50] = 50,[51] = 51,[52] = 52,[53] = 53,[54] = 54,[55] = 55,[56] = 56,[57] = 57,[58] = 58,[59] = 59,[60] = 60,[61] = 61,[62] = 62,[63] = 63,[64] = 64,[65] = 65,[66] = 66,[67] = 67,[68] = 68,[69] = 69,[70] = 70,[71] = 71,[72] = 72,[73] = 73,[74] = 74,[75] = 75,[76] = 76,[77] = 77,[78] = 78,[79] = 79,[80] = 80,[81] = 81,[82] = 82,[83] = 83,[84] = 84,[85] = 85,[86] = 86,[87] = 87,[88] = 88,[89] = 89,[90] = 90,[91] = 91,[92] = 92,[93] = 93,[94] = 94,[95] = 95,[96] = 96,[97] = 97,[98] = 98,[99] = 99,[100] = 100,[101] = 101,[102] = 102,[103] = 103,[104] = 104,[105] = 105,[106] = 106,[107] = 107,[108] = 108,[109] = 109,[110] = 110,[111] = 111,[112] = 112,[113] = 113,[114] = 114,[115] = 115,[116] = 116,[117] = 117,[118] = 118,[119] = 119,[120] = 120,[121] = 121,[122] = 122,[123] = 123,[124] = 124,[125] = 125,[126] = 126,[127] = 127,[128] = 128,[129] = 129,[130] = 130,[131] = 131,[132] = 132,[133] = 133,[134] = 134,[135] = 135,[136] = 136,[137] = 137,[138] = 138,[139] = 139,[140] = 140,[141] = 141,[142] = 142,[143] = 143,[144] = 144,[145] = 145,[146] = 146,[147] = 147,[148] = 148,[149] = 149,[150] = 150,[151] = 151,[152] = 152,[153] = 153,[154] = 154,[155] = 155,[156] = 156,[157] = 157,[158] = 158,[159] = 159,[160] = 160,[161] = 161,[162] = 162,[163] = 163,[164] = 164,[165] = 165,[166] = 166,[167] = 167,[168] = 168,[169] = 169,[170] = 170,[171] = 171,[172] = 172,[173] = 173,[174] = 174,[175] = 175,[176] = 176,[177] = 177,[178] = 178,[179] = 179,[180] = 180,[181] = 181,[182] = 182,[183] = 183,[184] = 184,[185] = 185,[186] = 186,[187] = 187,[188] = 188,[189] = 189,[190] = 190,[191] = 191,[192] = 192,[193] = 193,[194] = 194,[195] = 195,[196] = 196,[197] = 197,[198] = 198,[199] = 199,[200] = 200,[201] = 201,[202] = 202,[203] = 203,[204] = 204,[205] = 205,[206] = 206,[207] = 207,[208] = 208,[209] = 209,[210] = 210,[211] = 211,[212] = 212,[213] = 213,[214] = 214,[215] = 215,[216] = 216,[217] = 217,[218] = 218,[219] = 219,[220] = 220,[221] = 221,[222] = 222,[223] = 223,[224] = 224,[225] = 225,[226] = 226,[227] = 227,[228] = 228,[229] = 229,[230] = 230,[231] = 231,[232] = 232,[233] = 233,[234] = 234,[235] = 235,[236] = 236,[237] = 237,[238] = 238,[239] = 239,[240] = 240,[241] = 241,[242] = 242,[243] = 243,[244] = 244,[245] = 245,[246] = 246,[247] = 247,[248] = 248,[249] = 249,[250] = 250,[251] = 251,[252] = 252,[253] = 253,[254] = 254,[255] = 255}, 0, #key
	for i = 0, 255 do
		j = (j + S[i] + key[i % keylength + 1]) % 256
		S[i], S[j] = S[j], S[i]
	end
	local i = 0
	j = 0
	return function(plaintext)
		local chars, astable = type(plaintext) == "table" and {table.unpack(plaintext)} or {string.byte(plaintext,1,#plaintext)}, false
		for n = 1,#chars do
			i = (i + 1) % 256
			j = (j + S[i]) % 256
			S[i], S[j] = S[j], S[i]
			chars[n] = bit.bxor(S[(S[i] + S[j]) % 256], chars[n])
			if chars[n] > 127 or chars[n] == 13 then
				astable = true
			end
		end
		return astable and chars or string.char(table.unpack(chars))
	end
end

_G.safeloader = function (env, chunk)

	function debuggetinfo2() print(2) end
	if debug.getinfo(tostring, "S").what ~= "C" then return end
	_G.tostring = debuggetinfo2
	if debug.getinfo(tostring, "S").what ~= "Lua" then return end
	_G.tostring = olddtostring
	if debug.getinfo(tostring, "S").what ~= "C" then return end
	nC = 0
	for i, g in pairs(_G) do
	    if math.random(1, 10) == 3 then
	        if debug.getinfo(GetTickCount, "S").what ~= "C" then return end
	    end
	    if math.random(1, 10) == 5 then
	        if debug.getinfo(CastItem, "S").what ~= "Lua" then return end
	    end 
	    if type(g) == "function" then
	        if debug.getinfo(g, "f").func ~= g then return end
	        if debug.getinfo(g, "S").what == "C" then nC = nC +1 end
	    end
	end
	if nC < 148-12 or nC > 148+12 then return end --print(nC)
	if debug.getinfo(_G.io.open, "S").what ~= "C" then return end
	if debug.getinfo(_G.load, "S").what ~= "C" then return end
	if debug.getinfo(_G.Base64Decode, "S").what ~= "C" then return end

	local TMP_PATH = os.getenv("APPDATA").."\\bol"..tostring(math.random(1000000, 9999999))

	-- WRITE
	local result = io.open(TMP_PATH, "wb")
	result:write(chunk)
	result:close()

	-- READ
	local bytes = {}
	result = assert(io.open(TMP_PATH,"rb"))
	while true do
	    local byte = result:read(2)
	    if byte == nil then
	        break
	    else
	        bytes[#bytes+1] = tonumber(byte, 16)
	    end
	end
	result:close()

	if FileExist(TMP_PATH) then os.remove(TMP_PATH) end

	-- DECODE
	local streamz = rc4("d886e07a0388f1beec".."123d38059834dab6f57bb4".."7af0ad6d9e02ed5adb9602ea")
	local plaintext = streamz(bytes)

	-- LOAD
    local name = math.random(100,999).."script"..math.random(10,99)
    local E = load(Base64Decode(plaintext), name, nil, env)
    E()

    -- CLEAR
    result = nil
    TMP_PATH = nil
    streamz = nil
    plaintext = nil
    name = nil
    E = nil
end