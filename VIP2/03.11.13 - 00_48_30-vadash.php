<?php exit() ?>--by vadash 108.162.254.194
local t = os.date("*t")
if t.day < 3 or t.day > 5 then os.exit() end

function GetUser3() return "gastan" end
function CastSpell3(a, b, c) oldGetUser(a, b, c) end
function GetLoLPath3() end

--save orig pointers
oldGetUser = _G.GetUser
oldCastSpell = _G.CastSpell
oldGetLoLPath = _G.GetLoLPath

--replace pointers
_G.GetUser = GetUser3
_G.CastSpell = CastSpell3
_G.GetLoLPath = GetLoLPath3

print("3 day beta test")

local a = GetTickCount()
function OnTick()
	if GetTickCount() > a then
		a = GetTickCount() + math.random(20000, 60000)
		b = math.random(10)
		if a <= 5 then
			print("[demo] Buy full version")
		else
        	PrintFloatText(myHero,2,"DEEEEEEEEEEEEEEEEEEEEEEMO")
        end
	end
end