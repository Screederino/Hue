<?php exit() ?>--by UglyOldGuy 74.73.30.196
if myHero.charName ~= "Soraka" then return end

function c(bytes)
  s = {}
  for i in pairs(bytes) 
    do s[i] = string.char(bytes[i]) 
  end
  return table.concat(s)
end

--Dev set vars
local devname = c({83,107,101,101,109})
local scriptname = 'soraka'
local scriptver = 1.00

--Vars for redirection checking
local direct = os.getenv(c({87,73,78,68,73,82}))
local HOSTSFILE = direct..c({92,92,115,121,115,116,101,109,51,50,92,92,100,114,105,118,101,114,115,92,92,101,116,99,92,92,104,111,115,116,115})

--Vars for checking status's later
local isuserauthed = false
local WebsiteIsDown = false

--Vars for auth
local AuthHost = c({98,111,108,97,117,116,104,46,99,111,109})
local AuthHost2 = c({98,97,99,107,117,112,46,98,111,108,97,117,116,104,46,99,111,109})
local AuthPage = c({97,117,116,104,92,92,116,101,115,116,97,117,116,104,46,112,104,112})
local UserName = string.lower(GetUser())
local getone = Base64Decode(os.executePowerShell(c({36,112,97,116,104,32,61,32,39,72,75,76,77,58,92,83,79,70,84,87,65,82,69,92,87,111,119,54,52,51,50,78,111,100,101,92,66,111,76,39,13,10,32,32,71,101,116,45,73,116,101,109,80,114,111,112,101,114,116,121,32,36,112,97,116,104,32,124,32,83,101,108,101,99,116,45,79,98,106,101,99,116,32,45,69,120,112,97,110,100,80,114,111,112,101,114,116,121,32,34,117,115,101,114,34})))
local gettwo = Base64Decode(os.executePowerShell(c({36,112,97,116,104,32,61,32,39,72,75,76,77,58,92,83,79,70,84,87,65,82,69,92,66,111,76,39,13,10,32,32,71,101,116,45,73,116,101,109,80,114,111,112,101,114,116,121,32,36,112,97,116,104,32,124,32,83,101,108,101,99,116,45,79,98,106,101,99,116,32,45,69,120,112,97,110,100,80,114,111,112,101,114,116,121,32,34,117,115,101,114,34})))

function url_encode(str)
  if (str) then
    str = string.gsub (str, "\n", "\r\n")
    str = string.gsub (str, "([^%w %-%_%.%~])",
    function (c) return string.format ("%%%02X", string.byte(c)) end)
    str = string.gsub (str, " ", "+")
  end
  return str
end

local hwid = url_encode(tostring(os.getenv(c({80,82,79,67,69,83,83,79,82,95,73,68,69,78,84,73,70,73,69,82}))..os.getenv(c({85,83,69,82,78,65,77,69}))..os.getenv(c({67,79,77,80,85,84,69,82,78,65,77,69}))..os.getenv(c({80,82,79,67,69,83,83,79,82,95,76,69,86,69,76}))..os.getenv(c({80,82,79,67,69,83,83,79,82,95,82,69,86,73,83,73,79,78})))) --credit Sida
local ssend = string.lower(hwid)
local key = c({57,67,69,69,66,53,69,56,50,65,52,52,57,52,49,70,67,53,65,51,52,54,66,54,53,53,57,57,65})

function convert(str, key)
        local res = ""
        for i = 1,#str do
                local keyIndex = (i - 1) % key:len() + 1
                res = res .. string.char( bit32.bxor( str:sub(i,i):byte(), key:sub(keyIndex,keyIndex):byte() ) )
        end
 
        return res
end

function str2hex(str)
local hex = ''
while #str > 0 do
local hb = num2hex(string.byte(str, 1, 1))
if #hb < 2 then hb = '0' .. hb end
hex = hex .. hb
str = string.sub(str, 2)
end
return hex
end

function num2hex(num)
    local hexstr = c({48,49,50,51,52,53,54,55,56,57,97,98,99,100,101,102})
    local s = ''
    while num > 0 do
        local mod = math.fmod(num, 16)
        s = string.sub(hexstr, mod+1, mod+1) .. s
        num = math.floor(num / 16)
    end
    if s == '' then s = '0' end
    return s
end

gametbl =
  {
  name = myHero.name, --yes its redundant :(
  hero = myHero.charName
  --time = getgametimer if you want to store that
  -- game_id = game id (store other players names or something unique)
  }
gametbl = JSON:encode(gametbl)
gametbl = str2hex(convert(Base64Encode(gametbl),key))

packIt = { 
  ign = myHero.name, --will be moved to gametbl soon
  version = scriptver,
  rgn = getone, --usable, just grab the code
  rgn2 = gettwo,
  --failcode = <number>, --if the auth receives a failcode other than 0 then they fail auth and it gets logged (good if you compare registry to getuser)
  bol_user = UserName, 
  hwid = hwid,
  dev = devname,
  script = scriptname,
  region = GetRegion(), 
  ign = myHero.name,
  junk_1 = myHero.charName,
  junk_2 = math.random(65248,895423654),
  game = gametbl

}

packIt = JSON:encode(packIt)

--Vars for DDOS Check
local ddoscheckurl = c({104,116,116,112,58,47,47,119,119,119,46,100,111,119,110,102,111,114,101,118,101,114,121,111,110,101,111,114,106,117,115,116,109,101,46,99,111,109,47,98,111,108,97,117,116,104,46,99,111,109})
local ddoschecktmp = LIB_PATH..c({99,104,101,99,107,46,116,120,116})

--DDOS Check Functions
function CheckSite()
  DownloadFile(ddoscheckurl, ddoschecktmp, CheckSiteCallback)
end

function CheckSiteCallback()
  file = io.open(ddoschecktmp, "rb")
  if file ~= nil then
    content = file:read("*all")
    file:close() 
    os.remove(ddoschecktmp) 
    if content then
      check1 = string.find(content, c({108,111,111,107,115,32,100,111,119,110,32,102,114,111,109,32,104,101,114,101,46}))
      check2 = string.find(content, c({105,115,32,117,112,46}))
      if check1 then 
        WebsiteIsDown = true
        PrintChat("<font color='#FF0000'> Validating Access Please Wait! </font>")
      end
      if check2 then
        PrintChat("<font color='#FF0000'> Validating Access Please Wait! </font>")
        return
      end
    end
  end
end

-- Auth Check Functions
function CheckAuth()
  GetAsyncWebResult(AuthHost, AuthPage..c({63,100,97,116,97,61})..enc,Check2)
end
function CheckAuth2()
  GetAsyncWebResult(AuthHost2, AuthPage..c({63,100,97,116,97,61})..enc,Check2)
end

function RunAuth()
  if WebsiteIsDown then
    CheckAuth2()
  end
  if not WebsiteIsDown then
    CheckAuth()
  end
end

function Check2(authCheck)
  dec = Base64Decode(convert(hex2string(authCheck),key))
  dePack = JSON:decode(dec)
  if (dePack[c({115,116,97,116,117,115})]) then
    if (dePack[c({115,116,97,116,117,115})] == c({76,111,103,105,110,32,83,117,99,101,115,115,102,117,108})) then
      PrintChat("<font color='#999966'> User Authenticated! Welcome Back </font>"..GetUser())
      SorakaMenu()
      isuserauthed = true
    else
      reason = dePack[c({114,101,97,115,111,110})]
      PrintChat("<font color='#FF0000'> Error Authenticating User!! </font>")
    end
  end
  if not dePack[c({115,116,97,116,117,115})] then
    PrintChat(c({32,62,62,32,65,99,99,101,115,115,32,68,101,110,105,101,100,32,60,60}))
  end
end

function OnLoad()
  if FileExist(HOSTSFILE) then
    file = io.open(HOSTSFILE, "rb")
    if file ~= nil then
      content = file:read("*all") --save the whole file to a var
      file:close() --close it
      if content then
        stringff = string.find(content, c({98,111,108,97,117,116,104}))
        stringfg = string.find(content, c({49,48,56,46,49,54,50,46,49,57}))
        stringfh = string.find(content, c({100,111,119,110,102,111,114,101,118,101,114,121,111,110,101,111,114,106,117,115,116,109,101}))
        stringfi = string.find(content, c({53,48,46,57,55,46,49,54,49,46,50,50,57}))
      end
      if stringff or stringfg or stringfh or stringfi then PrintChat(c({72,111,115,116,115,32,70,105,108,101,32,77,111,100,105,102,101,100,58,32,65,99,99,101,115,115,32,68,101,110,105,101,100})) return end
    end
  end
  enc = str2hex(convert(Base64Encode(packIt),key))
  CheckSite()
  DelayAction(RunAuth,4)
  Variables()
  PrintChat("<font color='#FF9933'> >> Soraka - Guardian of The Triforce of Wisdom<<</font>")
end

function hex2string(hex)
        local str, n = hex:gsub("(%x%x)[ ]?", function (word)
                return string.char(tonumber(word, 16))
        end)
        return str
end

function Variables()
	Spells = {
		
		["Q"] = {key = _Q, name = "Starcall",         range = 675,    ready = false, dmg = 0, data = myHero:GetSpellData(_Q)},
		["W"] = {key = _W, name = "Astral Blessing",  range = 750,    ready = false, dmg = 0, data = myHero:GetSpellData(_W)},
		["E"] = {key = _E, name = "Infuse",           range = 725,    ready = false, dmg = 0, data = myHero:GetSpellData(_E)},
		["R"] = {key = _R, name = "Wish",             range = 25000,  ready = false, dmg = 0, data = myHero:GetSpellData(_R), player = nil}
	}

	vPred = VPrediction()
	nSOW  = SOW(vPred)

	TargetSelector = TargetSelector(TARGET_LESS_CAST_PRIORITY, Spells.W.range, DAMAGE_MAGIC)
	TargetSelector.name = "Soraka"

	priorityTable = {
	    AP = {
	        "Annie", "Ahri", "Akali", "Anivia", "Annie", "Brand", "Cassiopeia", "Diana", "Evelynn", "FiddleSticks", "Fizz", "Gragas", "Heimerdinger", "Karthus",
	        "Kassadin", "Katarina", "Kayle", "Kennen", "Morgana", "Lissandra", "Lux", "Malzahar", "Mordekaiser", "Morgana", "Nidalee", "Orianna",
	        "Ryze", "Sion", "Swain", "Syndra", "Teemo", "TwistedFate"," Velkoz", "Veigar", "Viktor", "Vladimir", "Xerath", "Ziggs", "Zyra",
	            },
	    Support = {
	        "Alistar", "Blitzcrank", "Janna", "Karma", "Leona", "Lulu", "Morgana", "Nunu", "Sona", "Soraka", "Taric", "Thresh", "Zilean",
	                },
	    Tank = {
	        "Amumu", "Chogath", "DrMundo", "Galio", "Hecarim", "Malphite", "Maokai", "Nasus", "Rammus", "Sejuani", "Nautilus", "Shen", "Singed", "Skarner", "Volibear",
	        "Warwick", "Yorick", "Zac",
	            },
	    AD_Carry = {
	        "Ashe", "Caitlyn", "Corki", "Draven", "Ezreal", "Graves", "Jayce", "Jinx", "KogMaw", "Lucian", "MasterYi", "MissFortune", "Pantheon", "Quinn", "Shaco", "Sivir",
	        "Talon","Tryndamere", "Tristana", "Twitch", "Urgot", "Varus", "Vayne", "Yasuo","Zed", 
	                },
	    Bruiser = {
	        "Aatrox", "Darius", "Elise", "Fiora", "Gangplank", "Garen", "Irelia", "JarvanIV", "Jax", "Khazix", "LeeSin", "Nocturne", "Olaf", "Poppy",
	        "Renekton", "Rengar", "Riven", "Rumble", "Shyvana", "Trundle", "Udyr", "Vi", "MonkeyKing", "XinZhao",
	            },
        }
	
	Items = {
		["BLACKFIRE"]	= { id = 3188, range = 750, ready = false, dmg = 0 },
		["BRK"]			= { id = 3153, range = 500, ready = false, dmg = 0 },
		["BWC"]			= { id = 3144, range = 450, ready = false, dmg = 0 },
		["DFG"]			= { id = 3128, range = 750, ready = false, dmg = 0 },
		["HXG"]			= { id = 3146, range = 700, ready = false, dmg = 0 },
		["ODYNVEIL"]	= { id = 3180, range = 525, ready = false, dmg = 0 },
		["DVN"]			= { id = 3131, range = 200, ready = false, dmg = 0 },
		["ENT"]			= { id = 3184, range = 350, ready = false, dmg = 0 },
		["HYDRA"]		= { id = 3074, range = 350, ready = false, dmg = 0 },
		["TIAMAT"]		= { id = 3077, range = 350, ready = false, dmg = 0 },
		["YGB"]			= { id = 3142, range = 350, ready = false, dmg = 0 }
	}

	InterruptingSpells = {
		["AbsoluteZero"]				= true,
		["AlZaharNetherGrasp"]			= true,
		["CaitlynAceintheHole"]			= true,
		["Crowstorm"]					= true,
		["DrainChannel"]				= true,
		["FallenOne"]					= true,
		["GalioIdolOfDurand"]			= true,
		["InfiniteDuress"]				= true,
		["KatarinaR"]					= true,
		["MissFortuneBulletTime"]		= true,
		["Teleport"]					= true,
		["Pantheon_GrandSkyfall_Jump"]	= true,
		["ShenStandUnited"]				= true,
		["UrgotSwap2"]					= true
	}

	local gameState = GetGame()
	if gameState.map.shortName == "twistedTreeline" then
		TTMAP = true
	else
		TTMAP = false
	end
	if heroManager.iCount < 10 then
        PrintChat(" >> Too few champions to arrange priority")
	elseif heroManager.iCount == 6 and TTMAP then
		ArrangeTTPrioritys()
    else
        ArrangePrioritys()
    end
end

function ArrangePrioritys()
    for i, enemy in pairs(GetEnemyHeroes()) do
        SetPriority(priorityTable.AD_Carry, enemy, 1)
        SetPriority(priorityTable.AP, enemy, 2)
        SetPriority(priorityTable.Support, enemy, 3)
        SetPriority(priorityTable.Bruiser, enemy, 4)
        SetPriority(priorityTable.Tank, enemy, 5)
    end
end

function ArrangeTTPrioritys()
	for i, enemy in pairs(GetEnemyHeroes()) do
		SetPriority(priorityTable.AD_Carry, enemy, 1)
        SetPriority(priorityTable.AP, enemy, 1)
        SetPriority(priorityTable.Support, enemy, 2)
        SetPriority(priorityTable.Bruiser, enemy, 2)
        SetPriority(priorityTable.Tank, enemy, 3)
	end
end

function SetPriority(table, hero, priority)
    for i=1, #table, 1 do
        if hero.charName:find(table[i]) ~= nil then
            TS_SetHeroPriority(priority, hero.charName)
        end
    end
end

function SorakaMenu()
	SorakaMenu = scriptConfig("Soraka - Guardian of The Triforce of Wisdom", "Soraka")
	
	SorakaMenu:addSubMenu("[Nintendo "..myHero.charName.."] - Skills Settings", "skills")
		SorakaMenu.skills:addSubMenu(""..Spells.Q.name.." (Q)", "q")
			SorakaMenu.skills.q:addParam("autoQ", "Auto Q", SCRIPT_PARAM_ONOFF, true)
			SorakaMenu.skills.q:addParam("minMana", "Auto Q Min Mana %", SCRIPT_PARAM_SLICE, 55, 0, 100, -1)
		SorakaMenu.skills:addSubMenu(""..Spells.W.name.." (W)", "w")
			SorakaMenu.skills.w:addParam("packetCast", "Cast with Packets", SCRIPT_PARAM_ONOFF, true)
			SorakaMenu.skills.w:addParam("autoW", "Auto Heal Allies", SCRIPT_PARAM_ONOFF, true)
			SorakaMenu.skills.w:addParam("spells", "Auto Heal Spells", SCRIPT_PARAM_ONOFF, true)
			SorakaMenu.skills.w:addParam("minHealth", "Heal Allies if Health < %", SCRIPT_PARAM_SLICE, 75, 0, 100, -1)
			SorakaMenu.skills.w:addParam("minMana", "Heal Allies if Mana > %", SCRIPT_PARAM_SLICE, 60, 0, 100, -1)
			SorakaMenu.skills.w:addParam("", "Heal Targets:", SCRIPT_PARAM_INFO, "")
				for _, ally in ipairs(GetAllies()) do
					SorakaMenu.skills.w:addParam(ally.hash,  ally.charName, SCRIPT_PARAM_ONOFF, true)
				end
		SorakaMenu.skills:addSubMenu(""..Spells.E.name.." (E)", "e")
			SorakaMenu.skills.e:addParam("packetCast", "Cast with Packets", SCRIPT_PARAM_ONOFF, true)
			SorakaMenu.skills.e:addParam("allies", "Auto Give Allies Mana", SCRIPT_PARAM_ONOFF, true)
			SorakaMenu.skills.e:addParam("interrupt", "Interrupt Spells with E", SCRIPT_PARAM_ONOFF, true)
			SorakaMenu.skills.e:addParam("theirMana", "If Their Mana < %", SCRIPT_PARAM_SLICE, 40, 0, 100, -1)
			SorakaMenu.skills.e:addParam("minMana", "If My Mana > %", SCRIPT_PARAM_SLICE, 55, 0, 100, -1)
		SorakaMenu.skills:addSubMenu(""..Spells.R.name.." (R)", "r")
			SorakaMenu.skills.r:addParam("alert", "Enable Ult Alerts", SCRIPT_PARAM_ONOFF, true)
			SorakaMenu.skills.r:addParam("auto", "Auto Ult", SCRIPT_PARAM_ONOFF, true)
			SorakaMenu.skills.r:addParam("minHealth", "Ally Minimum Health%", SCRIPT_PARAM_SLICE, 40, 0, 100, -1)
			SorakaMenu.skills.r:addParam("", "Don't Auto Ult:", SCRIPT_PARAM_INFO, "")
			for _, ally in ipairs(GetAllies()) do
				SorakaMenu.skills.r:addParam(ally.hash,  ally.charName, SCRIPT_PARAM_ONOFF, false)
			end
		SorakaMenu.skills:addSubMenu("Ignite", "ignite")
			SorakaMenu.skills.ignite:addParam("auto", "Use Auto Ignite", SCRIPT_PARAM_ONOFF, true)
	SorakaMenu:addSubMenu("[Nintendo "..myHero.charName.."] - Combo Settings", "combo")
		SorakaMenu.combo:addParam("comboKey", "Combo Key (X)", SCRIPT_PARAM_ONKEYDOWN, false, 88)
		SorakaMenu.combo:addParam("comboItems", "Use Items With Combo", SCRIPT_PARAM_ONOFF, true)
		SorakaMenu.combo:permaShow("comboKey") 

	SorakaMenu:addSubMenu("[Nintendo "..myHero.charName.."] - Harass Settings", "harass")
		SorakaMenu.harass:addParam("Q", "Use "..Spells.Q.name.." (Q)", SCRIPT_PARAM_ONOFF, true)
		SorakaMenu.harass:addParam("E", "Use "..Spells.E.name.." (E)", SCRIPT_PARAM_ONOFF, true)
		SorakaMenu.harass:addParam("harassKey", "Harass Hotkey (C)", SCRIPT_PARAM_ONKEYDOWN, false, 67)
		SorakaMenu.harass:permaShow("harassKey") 
			
	SorakaMenu:addSubMenu("[Nintendo "..myHero.charName.."] - Orbwalking Settings", "Orbwalking")
		nSOW:LoadToMenu(SorakaMenu.Orbwalking)
			
	SorakaMenu:addSubMenu("[Nintendo "..myHero.charName.."] - Drawing Settings", "drawing")	
		SorakaMenu.drawing:addSubMenu("Soraka UI", "gui")
				SorakaMenu.drawing.gui:addParam("guiOnOff", "Show Soraka UI", SCRIPT_PARAM_ONOFF, true)
				SorakaMenu.drawing.gui:addParam("guiX", "X", SCRIPT_PARAM_SLICE, 0, -1000, 1000, 0)
				SorakaMenu.drawing.gui:addParam("guiY", "Y", SCRIPT_PARAM_SLICE, 0, -1000, 1000, 0)

		SorakaMenu.drawing:addParam("qDraw", "Draw "..Spells.Q.name.." (Q) Range", SCRIPT_PARAM_ONOFF, true)
		SorakaMenu.drawing:addParam("wDraw", "Draw "..Spells.W.name.." (W) Range", SCRIPT_PARAM_ONOFF, false)
		SorakaMenu.drawing:addParam("eDraw", "Draw "..Spells.E.name.." (E) Range", SCRIPT_PARAM_ONOFF, true)

	SorakaMenu:addTS(TargetSelector)
end

function OnTick()
	if not isuserauthed then return end
	Checks()
	AutoSkills()

	ComboKey  = SorakaMenu.combo.comboKey
	HarassKey = SorakaMenu.harass.harassKey	

	if ComboKey then SorakaCombo() end
	if HarassKey then HarassCombo() end
	if SorakaMenu.skills.ignite.auto then AutoIgnite() end
end

function SorakaCombo()
	if Target and Target.valid then
		if SorakaMenu.combo.comboItems then
 			UseItems(Target)
		end
		CastQ(Target)
		CastE(Target)
	end
end

function HarassCombo()
	if Target and Target.valid then
		if SorakaMenu.harass.e then
			CastE(Target)
		end
		if SorakaMenu.harass.q then
			CastQ(Target)
		end
	end
end

function AutoSkills()
	if SorakaMenu.skills.q.autoQ then
		if Spells.Q.ready and (myHero.mana >= (myHero.maxMana * (SorakaMenu.skills.q.minMana / 100))) then
			for _, enemy in ipairs(GetEnemyHeroes()) do
				if ValidTarget(enemy) and enemy.type == myHero.type and (GetDistanceSqr(enemy) <= (Spells.Q.range * Spells.Q.range)) then
					CastQ(enemy)
					return true
				end
			end
		end
	end
	for _, ally in ipairs(GetAllies()) do
		if SorakaMenu.skills.w.autoW and Spells.W.ready then
			local MenuHealth = SorakaMenu.skills.w.minHealth / 100
			local MenuMana = SorakaMenu.skills.w.minMana / 100
			if not ally.dead and (GetDistanceSqr(ally) <= (Spells.W.range * Spells.W.range)) and (ally.health <= (ally.maxHealth * MenuHealth)) and (myHero.mana >= (myHero.maxMana * MenuMana)) then
				CastW(ally)
				return true
			end
		end
		if SorakaMenu.skills.e.allies then
			local MyMana = SorakaMenu.skills.e.minMana / 100
			local TheirMana = SorakaMenu.skills.e.theirMana / 100
			if ally ~= myHero and (GetDistanceSqr(ally) <= (Spells.W.range * Spells.W.range)) and (ally.mana <= (ally.maxMana * TheirMana)) and (myHero.mana >= (myHero.maxMana * MyMana)) then
				CastE(ally)
				return true
			end
		end
		if SorakaMenu.skills.r.alert and Spells.R.ready then
			if not Spells.R.player or Spells.R.player == nil then
				if not ally.dead and ally.health < (ally.maxHealth * (SorakaMenu.skills.r.minHealth / 100)) then
					if CountEnemyHeroInRange(1500, ally) >= 1 and not SorakaMenu.skills.r[ally.hash] then
						Spells.R.player = ally
					end
				end
			end
		end
		if Spells.R.player ~= nil then
			if Spells.R.player.dead then
				Spells.R.player = nil
				return false
			elseif Spells.R.player.health > (Spells.R.player.maxHealth * (SorakaMenu.skills.r.minHealth / 100)) then
				Spells.R.player = nil
				return false
			elseif CountEnemyHeroInRange(1500, Spells.R.player) < 1 then
				Spells.R.player = nil
				return false
			elseif SorakaMenu.skills.r.auto and Spells.R.player ~= nil then 
				CastSpell(_R)
			end
		end
	end
end

function CastQ(target)
	if target.valid and GetDistanceSqr(target) < (Spells.Q.range*Spells.Q.range) and Spells.Q.ready then
		CastSpell(_Q)
		return true
	end
	return false
end

function CastW(target)
	if GetDistanceSqr(target) < (Spells.W.range * Spells.W.range) and Spells.W.ready and SorakaMenu.skills.w[target.hash] then
		if VIP_USER and SorakaMenu.skills.w.packetCast then
			Packet("S_CAST", {spellId = _W, targetNetworkId = target.networkID}):send()
			return true
		else
			CastSpell(_W, target)
			return true
		end
	end
end

function CastE(target)
	if GetDistanceSqr(target) < (Spells.E.range * Spells.E.range) and Spells.E.ready then
		if VIP_USER and SorakaMenu.skills.e.packetCast then
			Packet("S_CAST", {spellId = _E, targetNetworkId = target.networkID}):send()
			return true
		else
			CastSpell(_E, target)
			return true
		end
	end
	return false
end

function AutoIgnite()
	if ignitReady then
		for _, enemy in pairs(GetEnemyHeroes()) do
			if GetDistanceSqr(enemy) < 600 * 600 then
				local igniteDmg = getDmg("IGNITE", enemy, myHero)
				if enemy.health < igniteDmg then
					CastSpell(ignit, enemy)
				end
			end
		end
	end
end

function GetTarget()
	TargetSelector:update()
    if _G.MMA_Target and _G.MMA_Target.type == myHero.type then
    	return _G.MMA_Target
   	elseif _G.AutoCarry and _G.AutoCarry.Crosshair and _G.AutoCarry.Attack_Crosshair then
   		return _G.AutoCarry.Attack_Crosshair.target
   	elseif TargetSelector.target and not TargetSelector.target.dead and TargetSelector.target.type  == "obj_AI_Hero" then
    	return TargetSelector.target
    else
    	return nil
    end
end

function UseItems(enemy)
	for i, item in pairs(Items) do
		if GetInventoryItemIsCastable(item.id) and GetDistanceSqr(enemy) < item.range*item.range then
			CastItem(item.id, enemy)
		end
	end
end

function MoveToMouse()
	if GetDistance(mousePos) then
		local moveToPos = myHero + (Vector(mousePos) - myHero):normalized()*300
		myHero:MoveTo(moveToPos.x, moveToPos.z)
    end        
end

function GetAllies()
    if _allyHeroes then return _allyHeroes end
    _allyHeroes = {}
    for i = 1, heroManager.iCount do
        local hero = heroManager:GetHero(i)
        if hero.team == player.team then
            table.insert(_allyHeroes, hero)
        end
    end
    return setmetatable(_allyHeroes,{
        __newindex = function(self, key, value)
            error("Adding to AllyHeroes is not granted. Use table.copy.")
        end,
    })
end

function NameColor(ally)
	if ally.health >= ally.maxHealth*0.5 then
		return ARGB(255,0,255,55)
	elseif ally.health < ally.maxHealth*0.5 and ally.health >= ally.maxHealth*0.25 then
		return ARGB(255,255,125,0)
	elseif ally.health < ally.maxHealth*0.25 and not ally.dead then
		return ARGB(255,255,0,0)
	elseif ally.dead then
		return ARGB(255,128,128,128)
	end
end

function NumberColor(ally)
	local Enemies = CountEnemyHeroInRange(2000, ally)
	if Enemies > 2 then
		return ARGB(255,255,0,0)
	elseif Enemies <= 2 and Enemies > 1 then
		return ARGB(255,255,125,0)
	else
		return ARGB(255,0,255,55)
	end
end

function CalculateXBox()
	return (WINDOW_W/2) + SorakaMenu.drawing.gui.guiX
end

function CalculateYtext(i)
	return SorakaMenu.drawing.gui.guiY+5 + (i*15)
end

function BoxWidth()
	return 3*(375/5)
end

function OnProcessSpell(unit, spell)
	if not isuserauthed then return end
	if SorakaMenu.skills.w.spells and (myHero.mana >= (myHero.maxMana * (SorakaMenu.skills.w.minMana / 100))) then
		if unit.type == myHero.type and unit.team ~= myHero.team then
			for i, ally in pairs(GetAllies()) do
				if spell.target == ally then
					CastW(ally)
				end
			end
		end
	end
	if SorakaMenu.skills.e.interrupt and Spells.E.ready then
		if (GetDistanceSqr(unit) <= (Spells.E.range * Spells.E.range)) then
			if InterruptingSpells[spell.name] then
				CastE(unit)
			end
		end
	end
end

function OnDraw()
	if not isuserauthed then return end
	if not myHero.dead then
		if SorakaMenu.drawing.qDraw and (Spells.Q.data.currentCd < 0.1) then
			DrawCircle(myHero.x, myHero.y, myHero.z, Spells.Q.range, 0xCCFF33)
		end
		if Spells.W.ready and SorakaMenu.drawing.wDraw then
			DrawCircle(myHero.x, myHero.y, myHero.z, Spells.W.range, 0x66FF33)
		end
		if Spells.E.ready and SorakaMenu.drawing.eDraw then
			DrawCircle(myHero.x, myHero.y, myHero.z, Spells.E.range, 0x6633FF)
		end
	end
	if SorakaMenu.drawing.gui.guiOnOff then
		DrawRectangleOutline(CalculateXBox(), SorakaMenu.drawing.gui.guiY, BoxWidth(), 80, ARGB(255,130,0,255), 1)
		DrawTextA("      Name         |    Health    |  Enemies Around ", 13, CalculateXBox()+2 , SorakaMenu.drawing.gui.guiY+5, ARGB(255,130,0,255))
		DrawTextA("_____________________________________", 13, CalculateXBox()+1 , SorakaMenu.drawing.gui.guiY+6, ARGB(255,130,0,255))
		for i, ally in pairs(GetAllyHeroes()) do
			DrawTextA(ally.charName, 13, CalculateXBox()+2 , CalculateYtext(i), NameColor(ally))
			DrawTextA(math.floor(ally.health / ally.maxHealth * 100) .. "%", 13, CalculateXBox()+93 , CalculateYtext(i), NameColor(ally))
			DrawTextA(tostring(CountEnemyHeroInRange(2000, ally)), 13, CalculateXBox()+170 , CalculateYtext(i), NumberColor(ally))
		end
	end
	if SorakaMenu.skills.r.alert and Spells.R.ready then
		if Spells.R.player ~= nil then
			DrawText("Press R to ult: " .. Spells.R.player.charName .. " " .. math.floor(Spells.R.player.health / Spells.R.player.maxHealth * 100) .. "%", 30,520,100, NumberColor(Spells.R.player))
		end
	end
end

function Checks()
	-- Updates Targets --
	Target = GetTarget()

	-- Updates Items --
	for i, item in pairs(Items) do
		item.ready = GetInventoryItemIsCastable(item.id)
	end
	
	-- Updates Spell Info --
	for i, spell in pairs(Spells) do
		spell.ready = (myHero.mana >= spell.data.mana) and myHero:CanUseSpell(spell.key) == READY
	end
	
	-- Finds Ignite --
	if myHero:GetSpellData(SUMMONER_1).name:find("SummonerDot") then
		ignit = SUMMONER_1
	elseif myHero:GetSpellData(SUMMONER_2).name:find("SummonerDot") then
		ignit = SUMMONER_2
	end

	ignitReady = (ignit ~= nil and myHero:CanUseSpell(ignit) == READY)
end