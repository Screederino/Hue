<?php exit() ?>--by x7x 89.70.161.4
local i101001011111111100100 = false
local i000001011111111100100 = false
local i010010101010101010103 = false
local i101001010011101001000 = "Ahri - Flying Balls"
local i101001011011101001000 = 0.967
local i001101010011101001000 = "<font color='#00BFFF'>AUTOUPDATER: Script updated! Reload scripts to use new version!</font>"
local i001101010001101001000 = "<font color='#00BFFF'>AUTOUPDATER: There is a new version of "..i101001010011101001000..". Don't F9 till done...</font>"
local i001101010001111001000 = "<font color='#00BFFF'>AUTOUPDATER: Cant connect to server, maybe check your firewall?</font>"
local i010010101010101010102 = "<font color='#00BFFF'>Unauthorized user! Get license for: "..GetUser()..".</font>"
local ii101001010010101001000 = false
local i10100101001010100100 = true
local i001101010011101001111 = BOL_PATH.."Scripts\\"..i101001010011101001000..".lua"
function i101001010010101001001(data)
	local i001100110011101001111 = tonumber(data)
	if type(i001100110011101001111) ~= "number" then return end
	if i001100110011101001111 and i001100110011101001111 > i101001011011101001000 then
		print(i001101010001101001000) 
		ii101001010010101001000 = true
	elseif i001100110011101001111 == nil then
		PrintChat(i001101010001111001000)
	else
		i101001011111110100100 = true
	end
end
function i101101010011101001000()
	if i10100101001010100100 then
		i10100101001010100100 = false
		GetAsyncWebResult("noobkillerpl.cuccfree.org", "AhriVer.lua", i101001010010101001001)
	end

	if ii101001010010101001000 then
		ii101001010010101001000 = false
		DownloadFile("http://noobkillerpl.cuccfree.org/Ahri.lua", i001101010011101001111, function()
                if FileExist(i001101010011101001111) then
                    print(i001101010011101001000)
                end
            end)
	end
end
AddTickCallback(i101101010011101001000)
local i000101011111111100100 = true
local tECollision = nil
local Prodict = ProdictManager.GetInstance()
local QProdict = nil
local EProdict = nil
local ts = TargetSelector(TARGET_LESS_CAST_PRIORITY,1250,DAMAGE_MAGICAL,false)
local Enemies = {}
local LastAATarget = nil
local NextAATick = 0
local EndOfWindupTick = 0
local AutoAttackRange = 550
local ECollision
local CanQ = false
local CanW = false
local CanE = false
local CanR = false
local CanIgnite = false
local CanDFG = false
local JumpStacks = 0
local waittxt = {}
local EndOfR = 0
local AhriOrb = nil
local CurrentDPS = 0
function GetUltStacks()
	if i101001011111110100100 and i101001011111111100100 then
		if CanR and JumpStacks == 0 then
			return 3
		elseif JumpStacks == 2 then
			return 2
		elseif JumpStacks == 1 then
			return 1
		else
			return 0
		end
	else
		return 4
	end
end
function aCheckLicense()
	local i101001010111110100100 = 1
	if i101001010111110100100 == 2 then
		local i3123019301239120391203910 = {"x9x", "CodeX1337"}
		local i101001010111110100100 = false
		for i, a in pairs(i3123019301239120391203910) do
			local s = tostring(GetUser())
			if a == s then
				i101001010111110100100 = true
				p = true
				local License = true
			else
				i101001010111110100100 = false
				i101001010111110100100 = false
			end
		end
	end
end
function OnUpdateBuff(unit, buff)
	if unit.isMe and buff.name == "AhriTumble" then JumpStacks = buff.stack end
end
function OnGainBuff(unit, buff)
	if unit.isMe and buff.name == "AhriTumble" then
		JumpStacks = buff.stack
		EndOfR = GetTickCount()+10000
	end
end
function OnLoseBuff(unit, buff)
	if unit.isMe and buff.name == "AhriTumble" then JumpStacks = 0 end
end
function CheckBuffs()
	for i=1, myHero.buffCount do
		local buff = myHero:getBuff(i)
		if buff.name == "AhriTumble" then
			PrintChat("AhriULT")
		end
	end
end
function OnLoad()
	if 1193481290437048832047230840238493434237082646 == 371298423975632495762089471894792427 then
	Menu = scriptConfig("aAhri Flyni balz v"..versionGOE, "akatarina")
	ts.name = "ahri"
	Menu:addTS(ts)
	Menu:addSubMenu("General information", "InfoMenu")
	Menu.InfoMenu:addParam("Information1", "AhriBetaDonator v"..versionGOE.." created by Anonymous.", SCRIPT_PARAM_INFO, "")
	Menu.InfoMenu:addParam("Information2", "Enjoy and report bugs on forums!", SCRIPT_PARAM_INFO, "")
	Menu:addSubMenu("Combo settings [SBTW]", "ComboHelperMenu")
	Menu.ComboHelperMenu:addParam("ComboHelper","Combo key", SCRIPT_PARAM_ONKEYDOWN, false, 32)
	Menu.ComboHelperMenu:addParam("useQ","Use Q automaticly", SCRIPT_PARAM_ONOFF, true)
	Menu.ComboHelperMenu:addParam("useW","Use W automaticly", SCRIPT_PARAM_ONOFF, true)
	Menu.ComboHelperMenu:addParam("useE","Use E automaticly", SCRIPT_PARAM_ONOFF, true)
	Menu.ComboHelperMenu:addParam("useR","Use R automaticly", SCRIPT_PARAM_ONOFF, true)
	Menu.ComboHelperMenu:addParam("UseMEC","Use MEC for ultimate", SCRIPT_PARAM_ONOFF, true)
	Menu:addSubMenu("Drawer settings", "DrawHelperMenu")
	Menu.DrawHelperMenu:addParam("drawQrange","Draw Q range", SCRIPT_PARAM_ONOFF, false)
	Menu.DrawHelperMenu:addParam("drawWrange","Draw W range", SCRIPT_PARAM_ONOFF, false)
	Menu.DrawHelperMenu:addParam("drawErange","Draw E range", SCRIPT_PARAM_ONOFF, true)
	Menu.DrawHelperMenu:addParam("drawRrange","Draw R range", SCRIPT_PARAM_ONOFF, false)
	Menu.DrawHelperMenu:addParam("drawHPtext","Draw damage output info", SCRIPT_PARAM_ONOFF, true)
	Menu:addSubMenu("Other settings", "OtherMenu")
	Menu.OtherMenu:addParam("useKSfunc","Automaticly secure kills", SCRIPT_PARAM_ONOFF, true)
	Menu.OtherMenu:addParam("canKScancel","Can KS func cancel ultimate", SCRIPT_PARAM_ONOFF, false)
	Menu.OtherMenu:addParam("useWards","Use wardjumps in KS combo", SCRIPT_PARAM_ONOFF, true)
	local ordertxt = 1
	for i=1, heroManager.iCount do
		waittxt[i] = ordertxt
		ordertxt = ordertxt+1
		local EnemyHero = heroManager:getHero(i)
		if EnemyHero ~= nil and EnemyHero.team == TEAM_ENEMY then table.insert(Enemies, EnemyHero) end
	end
	PrintChat("<font color='#00BFFF'>license "..versionGOE.." loaded!</font>")
	end
end

function OnUnload()
	if i101001011111110100100 and i101001011111111100100 then
		PrintChat("<font color='#00BFFF'>"..i101001010011101001000.." v"..i101001011011101001000.." unloaded!</font>")
	end
end
function f123f1231231232f13123123()
	local i3123019301239120391203910 = {"x7x", "CodeX1337"}
	local p = false
	for i, x in pairs(i3123019301239120391203910) do
		local u = tostring(GetUser())
		if x == u then
			i101001011111111100100 = true
			p = true
		end
	end
end

function OnCreateObj(obj)
	if i101001011111110100100 and i101001011111111100100 then
		if obj ~= nil and obj.valid and obj.name == "Ahri_Orb_mis.troy123" or obj ~= nil and obj.valid and obj.name == "Ahri_Orb_mis_02.troy" then AhriOrb = obj end
	end
end
if myHero.name == "isDonator" then
function iOnLoad()
	Menu = scriptConfig("aKatarina v"..versionGOE, "akatarina")
	ts.name = "aKatarina"
	Menu:addTS(ts)
	Menu:addSubMenu("General information", "InfoMenu")
	Menu.InfoMenu:addParam("Information1", "aKatarina v"..versionGOE.." created by Anonymous.", SCRIPT_PARAM_INFO, "")
	Menu.InfoMenu:addParam("Information2", "Enjoy and report bugs on forums!", SCRIPT_PARAM_INFO, "")
	Menu:addSubMenu("Combo settings [SBTW]", "ComboHelperMenu")
	Menu.ComboHelperMenu:addParam("ComboHelper","Combo key", SCRIPT_PARAM_ONKEYDOWN, false, 32)
	Menu.ComboHelperMenu:addParam("useQ","Use Q automaticly", SCRIPT_PARAM_ONOFF, true)
	Menu.ComboHelperMenu:addParam("useW","Use W automaticly", SCRIPT_PARAM_ONOFF, true)
	Menu.ComboHelperMenu:addParam("useE","Use E automaticly", SCRIPT_PARAM_ONOFF, true)
	Menu.ComboHelperMenu:addParam("useR","Use R automaticly", SCRIPT_PARAM_ONOFF, true)
	Menu.ComboHelperMenu:addParam("UseMEC","Use MEC for ultimate", SCRIPT_PARAM_ONOFF, true)
	Menu:addSubMenu("Drawer settings", "DrawHelperMenu")
	Menu.DrawHelperMenu:addParam("drawQrange","Draw Q range", SCRIPT_PARAM_ONOFF, false)
	Menu.DrawHelperMenu:addParam("drawWrange","Draw W range", SCRIPT_PARAM_ONOFF, false)
	Menu.DrawHelperMenu:addParam("drawErange","Draw E range", SCRIPT_PARAM_ONOFF, true)
	Menu.DrawHelperMenu:addParam("drawRrange","Draw R range", SCRIPT_PARAM_ONOFF, false)
	Menu.DrawHelperMenu:addParam("drawHPtext","Draw damage output info", SCRIPT_PARAM_ONOFF, true)
	Menu:addSubMenu("Other settings", "OtherMenu")
	Menu.OtherMenu:addParam("useKSfunc","Automaticly secure kills", SCRIPT_PARAM_ONOFF, true)
	Menu.OtherMenu:addParam("canKScancel","Can KS func cancel ultimate", SCRIPT_PARAM_ONOFF, false)
	Menu.OtherMenu:addParam("useWards","Use wardjumps in KS combo", SCRIPT_PARAM_ONOFF, true)
	local ordertxt = 1
	for i=1, heroManager.iCount do
		waittxt[i] = ordertxt
		ordertxt = ordertxt+1
		local EnemyHero = heroManager:getHero(i)
		if EnemyHero ~= nil and EnemyHero.team == TEAM_ENEMY then table.insert(Enemies, EnemyHero) end
	end
	PrintChat("<font color='#00BFFF'>aKatarina "..versionGOE.." loaded!</font>")
end

function iOnTick()
	ts:update()
	enemyMinions:update()
	allyMinions:update()
	IsStillUlting()
	if CastR == true then CastRfunc() end
	local CanQ = (myHero:CanUseSpell(_Q) == READY)
	local CanW = (myHero:CanUseSpell(_W) == READY)
	local CanE = (myHero:CanUseSpell(_E) == READY)
	local CanR = (myHero:CanUseSpell(_R) == READY)
	if ultActive == true and CountEnemyHeroInRange(550) == 0 then ultActive = false end
	if Menu.ComboHelperMenu.ComboHelper and NextComboTick < GetTickCount() and not ultActive and not CastR then
		if ts.target ~= nil then
			local Qdmg = 0
			local Wdmg = 0
			local Edmg = 0
			if CanQ then Qdmg = getDmg("Q", ts.target, myHero) end
			if CanW then Wdmg = getDmg("W", ts.target, myHero) end
			if CanE then Edmg = getDmg("E", ts.target, myHero) end
			if ts.target.health < Qdmg and GetDistance(ts.target) < 675 and CanQ then
				if Menu.ComboHelperMenu.useQ then CastSpell(_Q, ts.target) end
			else
				if Menu.ComboHelperMenu.UseMEC and CanE and CanR then
					local spellPos = GetAoESpellPosition(550, ts.target)
					local ClosestToMEC = ts.target
					for _, target in pairs(enemyMinions.objects) do
						if target.valid and not target.dead and GetDistance(target, spellPos) < GetDistance(ts.target, spellPos) and GetDistance(target) < 700 then
							ClosestToMEC = target
						end
					end
					for _, target in pairs(allyMinions.objects) do
						if target.valid and not target.dead and GetDistance(target, spellPos) < GetDistance(ts.target, spellPos) and GetDistance(target) < 700 then
							ClosestToMEC = target
						end
					end
					for _, target in pairs(Enemies) do
						if target.valid and not target.dead and GetDistance(target, spellPos) < GetDistance(ts.target, spellPos) and GetDistance(target) < 700 then
							ClosestToMEC = target
						end
					end
					CastSpell(_E, ClosestToMEC)
				else
					if CanE and GetDistance(ts.target) < 700 then if Menu.ComboHelperMenu.useE then CastSpell(_E, ts.target) end end
				end
				if GetInventoryItemIsCastable(3128) then CastItem(3128, ts.target) end
				if CanQ and GetDistance(ts.target) < 675 then if Menu.ComboHelperMenu.useQ then CastSpell(_Q, ts.target) end end
				if CanW and GetDistance(ts.target) < 375 then if Menu.ComboHelperMenu.useW then
					CastSpell(_W)
				end end
				if CanR and not CanE and not CanW and not CanQ and GetDistance(ts.target) < 550 then
					NextComboTick = GetTickCount() + 5
					CastSpell(_R)
				end
			end
		end
	end
	if Menu.OtherMenu.useKSfunc and NextComboTick < GetTickCount() and not ultActive or Menu.OtherMenu.useKSfunc and Menu.OtherMenu.canKScancel then
		for _, Target in pairs(Enemies) do
			if Target ~= nil and Target.valid and not Target.dead and Target.team == TEAM_ENEMY and GetDistance(Target) < 2000 and ValidTarget(Target) then
				local Qdmg = 0
				local Wdmg = 0
				local Edmg = 0
				if CanQ then Qdmg = getDmg("Q", Target, myHero) end
				if CanW then Wdmg = getDmg("W", Target, myHero) end
				if CanE then Edmg = getDmg("E", Target, myHero) end
				if CanW and Wdmg > Target.health and GetDistance(Target) < 375 then
					CastSpell(_W)
				elseif CanQ and Qdmg > Target.health and GetDistance(Target) < 675 then
					CastSpell(_Q, Target)
				elseif CanE and Edmg > Target.health and GetDistance(Target) < 700 then
					CastSpell(_E, Target)
				elseif CanQ and CanW and Qdmg+Wdmg > Target.health and GetDistance(Target) < 375 then
					CastSpell(_Q, Target)
					CastSpell(_W)
				elseif CanE and CanW and Wdmg+Edmg > Target.health and GetDistance(Target) < 700 then
					CastSpell(_E, Target)
				elseif CanE and CanQ and Qdmg+Edmg > Target.health and GetDistance(Target) < 700 then
					CastSpell(_E, Target)
					CastSpell(_Q, Target)
				elseif CanQ and CanW and CanE and Qdmg+Wdmg+Edmg > Target.health and GetDistance(Target) < 700 then
					CastSpell(_E, Target)
					CastSpell(_Q, Target)
					CastSpell(_W)
				elseif Menu.OtherMenu.useWards and CanE and CanW and Wdmg > Target.health and GetDistance(Target) > 700 and GetDistance(Target) < 975 then
					WardJump(Target.x, Target.z, 50)
					if GetDistance(Target) < 375 then CastSpell(_W) end
				elseif Menu.OtherMenu.useWards and CanE and CanQ and Qdmg > Target.health and GetDistance(Target) > 700 and GetDistance(Target) < 1275 then
					WardJump(Target.x, Target.z, 50)
					if GetDistance(Target) < 675 then CastSpell(_Q, Target) end
				elseif Menu.OtherMenu.useWards and CanE and CanQ and CanW and Qdmg+Wdmg > Target.health and GetDistance(Target) > 700 and GetDistance(Target) < 975 then
					WardJump(Target.x, Target.z, 50)
					if GetDistance(Target) < 375 then CastSpell(_W) end
					if GetDistance(Target) < 675 then CastSpell(_Q, Target) end
				end
			end
		end
	end
end

function iCastTrinket(xPos, yPos)
	local p = CLoLPacket(0x9A)
	p.dwArg1 = 1
	p.dwArg2 = 0
	p:EncodeF(myHero.networkID)
	p:Encode1(10)
	p:EncodeF(mousePos.x)
	p:EncodeF(mousePos.z)
	p:EncodeF(xPos)
	p:EncodeF(yPo)
	p:EncodeF(0)
	SendPacket(p)
end

function iCastRfunc()
	local CanQ = (myHero:CanUseSpell(_E) == READY)
	local CanW = (myHero:CanUseSpell(_E) == READY)
	local CanE = (myHero:CanUseSpell(_E) == READY)
	local CanR = (myHero:CanUseSpell(_R) == READY)
	if CastR then
		if CanR and GetTickCount() > CastRtick then
			ultActive = true
			CastR = false
			if Menu.ComboHelperMenu.UseMEC and CanE and CountEnemyHeroInRange(1200) > 1 then
				local spellPos = GetAoESpellPosition(550, ts.target)
				local ClosestToMEC = ts.target
				for _, target in pairs(enemyMinions.objects) do
					if target.valid and not target.dead and GetDistance(target, spellPos) < GetDistance(ts.target, spellPos) and GetDistance(target) < 700 then
						ClosestToMEC = target
					end
				end
				for _, target in pairs(allyMinions.objects) do
					if target.valid and not target.dead and GetDistance(target, spellPos) < GetDistance(ts.target, spellPos) and GetDistance(target) < 700 then
						ClosestToMEC = target
					end
				end
				for _, target in pairs(Enemies) do
					if target.valid and not target.dead and GetDistance(target, spellPos) < GetDistance(ts.target, spellPos) and GetDistance(target) < 700 then
						ClosestToMEC = target
					end
				end
				CastSpell(_E, ClosestToMEC)
				if GetDistance(ClosestToMEC) < 250 then if Menu.ComboHelperMenu.useR then CastSpell(_R) end end
			elseif CountEnemyHeroInRange(1200) == 1 then
				if GetDistance(ts.target) < 250 then if Menu.ComboHelperMenu.useR then CastSpell(_R) end end
			end
		end
	end
end

function iOnDraw()
	if Menu.DrawHelperMenu.drawQrange then DrawCircle(myHero.x, myHero.y, myHero.z, 675, RGB(0,191,255)) end
	if Menu.DrawHelperMenu.drawWrange then DrawCircle(myHero.x, myHero.y, myHero.z, 375, RGB(0,191,255)) end
	if Menu.DrawHelperMenu.drawErange then DrawCircle(myHero.x, myHero.y, myHero.z, 700, RGB(0,191,255)) end
	if Menu.DrawHelperMenu.drawRrange then DrawCircle(myHero.x, myHero.y, myHero.z, 550, RGB(0,191,255)) end
	if Menu.DrawHelperMenu.drawHPtext then
		local CanQ = (myHero:CanUseSpell(_Q) == READY)
		local CanW = (myHero:CanUseSpell(_W) == READY)
		local CanE = (myHero:CanUseSpell(_E) == READY)
		local CanR = (myHero:CanUseSpell(_R) == READY)
		local CanDFG = GetInventoryItemIsCastable(3128)
		for n, DrawTarget in pairs(Enemies) do
			if DrawTarget ~= nil and DrawTarget.valid and not DrawTarget.dead and DrawTarget.team == TEAM_ENEMY then
				local DFGdmg = 0
				local TotalDMG = 0
				local Qdmg = getDmg("Q", DrawTarget, myHero)
				local Wdmg = getDmg("W", DrawTarget, myHero)
				local Edmg = getDmg("E", DrawTarget, myHero)
				local Rdmg = getDmg("R", DrawTarget, myHero)*10
				if CanDFG then
					TotalDMG = (Qdmg+Wdmg+Edmg+Rdmg)*1.2
					TotalDMG = TotalDMG + getDmg("DFG", DrawTarget, myHero)
				else
					TotalDMG = Qdmg+Wdmg+Edmg+Rdmg
				end
				if Qdmg+Wdmg+Edmg > DrawTarget.health then
					if waittxt[n] == 1 then 
						PrintFloatText(DrawTarget, 0, "MURDER HIM")
						waittxt[n] = 10
					else
						waittxt[n] = waittxt[n]-1
					end
				elseif TotalDMG > DrawTarget.health then
					if waittxt[n] == 1 then 
						PrintFloatText(DrawTarget, 0, "Killable")
						waittxt[n] = 10
					else
						waittxt[n] = waittxt[n]-1
					end
				else
					if waittxt[n] == 1 then 
						PrintFloatText(DrawTarget, 0, "Not killable")
						waittxt[n] = 10
					else
						waittxt[n] = waittxt[n]-1
					end
				end
			end
		end	
	end
end
function iOnCreateObj(obj)
	if obj ~= nil and obj.valid then
		if obj.name == "SightWard" or obj.name == "VisionWard" then
			table.insert(Wards, obj)
		end
	end
end

function iWardJump(x,y, maxrange)
	local PlacedObj = false
	local JumpTarget = nil
	if maxrange == nil then maxrange = Menu.maxdistance end
	if LastJump+250 < GetTickCount() and not Jumped then
		local jumpTo = Point(0,0)
		if x ~= nil and y ~= nil then
			jumpTo = GetDistance(Point(x,y)) < JumpAbilityRange and Point(x, y) or Point(myHero.x, myHero.z)-(Point(myHero.x, myHero.z)-Point(x, y)):normalized()*JumpAbilityRange
		else
			jumpTo = GetDistance(mousePos) < JumpAbilityRange and Point(mousePos.x, mousePos.z) or Point(myHero.x, myHero.z)-(Point(myHero.x, myHero.z)-Point(mousePos.x, mousePos.z)):normalized()*JumpAbilityRange
		end
		table.sort(Wards, function(a,b) return GetDistance(a) < GetDistance(b) end)
		for i, Ward in pairs(Wards) do
			if Ward == nil or not Ward.valid or Ward.dead then
				table.remove(Wards, i)
				i = i - 1
			else
				if Ward ~= nil and Ward.health > 0 and Ward.valid then
					if GetDistance(Ward, jumpTo) <= maxrange then
						JumpTarget = Ward
					end	
				end
			end
		end	
		if JumpTarget ~= nil then
			if myHero:CanUseSpell(GlobalJumpAbility) == READY then
				CastSpell(GlobalJumpAbility, JumpTarget)
				Jumped = true
				LastJump = GetTickCount()
			end
		else
			if PlacedObj == false and LastPlacedObject+250 < GetTickCount() and myHero:CanUseSpell(GlobalJumpAbility) == READY then
				local JumpingSlot = nil
				if GetInventorySlotItem(2045) ~= nil and myHero:CanUseSpell(GetInventorySlotItem(2045)) == READY then
					JumpingSlot = GetInventorySlotItem(2045)
				elseif GetInventorySlotItem(2049) ~= nil and myHero:CanUseSpell(GetInventorySlotItem(2049)) == READY then
					JumpingSlot = GetInventorySlotItem(2049)
				elseif myHero:CanUseSpell(ITEM_7) == READY and myHero:getItem(ITEM_7).id == 3340 or myHero:CanUseSpell(ITEM_7) == READY and myHero:getItem(ITEM_7).id == 3350 or myHero:CanUseSpell(ITEM_7) == READY and myHero:getItem(ITEM_7).id == 3361 or myHero:CanUseSpell(ITEM_7) == READY and myHero:getItem(ITEM_7).id == 3362 then
					JumpingSlot = ITEM_7
				elseif GetInventorySlotItem(2044) ~= nil and myHero:CanUseSpell(GetInventorySlotItem(2044)) == READY then
					JumpingSlot = GetInventorySlotItem(2044)
				elseif GetInventorySlotItem(2043) ~= nil and myHero:CanUseSpell(GetInventorySlotItem(2043)) == READY then
					JumpingSlot = GetInventorySlotItem(2043)
				end
				if JumpingSlot ~= nil then
					CastSpell(JumpingSlot, jumpTo.x, jumpTo.y)
					PlacedObj = true
					LastPlacedObject = GetTickCount()
				end
			end
		end
	else
		Jumped = false
		JumpTarget = nil
		PlacedObj = false
	end
end
function iOnProcessSpell(me, spell)
	if me.isMe and spell.name == "KatarinaR" then
		ultActive = true
		dontMoveUntil = GetTickCount() + 100
	end
end

function iOnAnimation(me, anim)
	if me.isMe and anim ~= lastAnimation then lastAnimation = anim end
end

function iIsStillUlting()
	if ultActive then
		if lastAnimation ~= "Spell4" then
			ultActive = false
		end
	else
		ultActive = false
	end
end

function iOnUnload()
	PrintChat("<font color='#00BFFF'>aKatarina "..versionGOE.." unloaded :(!</font>")
end

function iOnSendPacket(packet)
	local p = Packet(packet)
end
 
function iGetCenter(points)
        local sum_x = 0
        local sum_z = 0
       
        for i = 1, #points do
                sum_x = sum_x + points[i].x
                sum_z = sum_z + points[i].z
        end
       
        local center = {x = sum_x / #points, y = 0, z = sum_z / #points}
       
        return center
end
 
function iContainsThemAll(circle, points)
        local radius_sqr = circle.radius*circle.radius
        local contains_them_all = true
        local i = 1
       
        while contains_them_all and i <= #points do
                contains_them_all = GetDistanceSqr(points[i], circle.center) <= radius_sqr
                i = i + 1
        end
       
        return contains_them_all
end
 
function iFarthestFromPositionIndex(points, position)
        local index = 2
        local actual_dist_sqr
        local max_dist_sqr = GetDistanceSqr(points[index], position)
       
        for i = 3, #points do
                actual_dist_sqr = GetDistanceSqr(points[i], position)
                if actual_dist_sqr > max_dist_sqr then
                        index = i
                        max_dist_sqr = actual_dist_sqr
                end
        end
       
        return index
end
 
function iRemoveWorst(targets, position)
        local worst_target = FarthestFromPositionIndex(targets, position)
       
        table.remove(targets, worst_target)
       
        return targets
end
 
function iGetInitialTargets(radius, main_target)
        local targets = {main_target}
        local diameter_sqr = 4 * radius * radius
       
        for i=1, heroManager.iCount do
                target = heroManager:GetHero(i)
                if target.networkID ~= main_target.networkID and ValidTarget(target) and GetDistanceSqr(main_target, target) < diameter_sqr then table.insert(targets, target) end
        end
       
        return targets
end
 
function iGetPredictedInitialTargets(radius, main_target, delay)
        if VIP_USER and not vip_target_predictor then vip_target_predictor = TargetPredictionVIP(nil, nil, delay/1000) end
        local predicted_main_target = VIP_USER and vip_target_predictor:GetPrediction(main_target) or GetPredictionPos(main_target, delay)
        local predicted_targets = {predicted_main_target}
        local diameter_sqr = 4 * radius * radius
       
        for i=1, heroManager.iCount do
                target = heroManager:GetHero(i)
                if ValidTarget(target) then
                        predicted_target = VIP_USER and vip_target_predictor:GetPrediction(target) or GetPredictionPos(target, delay)
                        if target.networkID ~= main_target.networkID and GetDistanceSqr(predicted_main_target, predicted_target) < diameter_sqr then table.insert(predicted_targets, predicted_target) end
                end
        end
       
        return predicted_targets
end
 
function iGetAoESpellPosition(radius, main_target, delay)
        local targets = delay and GetPredictedInitialTargets(radius, main_target, delay) or GetInitialTargets(radius, main_target)
        local position = GetCenter(targets)
        local best_pos_found = true
        local circle = Circle(position, radius)
        circle.center = position
       
        if #targets > 2 then best_pos_found = ContainsThemAll(circle, targets) end
       
        while not best_pos_found do
                targets = RemoveWorst(targets, position)
                position = GetCenter(targets)
                circle.center = position
                best_pos_found = ContainsThemAll(circle, targets)
        end
       
        return position
end
end
local printd = false
function OnTick()
	if i010010101010101010103 and not i101001011111111100100 and not printd then
		printd = true
		PrintChat(i010010101010101010102)
	end	
	if i101001011111110100100 then
		if i101001011111111100100 and i000101011111111100100 then
			QProdict = Prodict:AddProdictionObject(_Q, 1000, 1700, 0.24, 50)
			EProdict = Prodict:AddProdictionObject(_E, 1000, 1550, 0.25, 60)
			tECollision = Collision(900, 1550, 0.24, 60)
			Menu = scriptConfig(i101001010011101001000.." v"..i101001011011101001000, "aahri")
			ts.name = "Ahri TS"
			Menu:addTS(ts)
			Menu:addSubMenu("General information", "InfoMenu")
			Menu.InfoMenu:addParam("Information1", "    Ahri - Flying Balls v"..i101001011011101001000, SCRIPT_PARAM_INFO, "")
			Menu.InfoMenu:addParam("Information2", "Huge thanks for donation and support!", SCRIPT_PARAM_INFO, "")
			Menu.InfoMenu:addParam("Information3", "Enjoy script and report bugs on forums!", SCRIPT_PARAM_INFO, "")
			Menu:addSubMenu("Combo settings", "ComboMenu")
			Menu.ComboMenu:addParam("ComboKey","Combo hotkey", SCRIPT_PARAM_ONKEYDOWN, false, 32)
			Menu.ComboMenu:addParam("useAA","Orbwalking", SCRIPT_PARAM_ONOFF, true)
			Menu.ComboMenu:addParam("smartQWER","Use smart QWER combo", SCRIPT_PARAM_ONOFF, true)
			Menu.ComboMenu:addParam("useQ","Use Q automaticly", SCRIPT_PARAM_ONOFF, true)
			Menu.ComboMenu:addParam("useW","Use W automaticly", SCRIPT_PARAM_ONOFF, true)
			Menu.ComboMenu:addParam("useE","Use E automaticly", SCRIPT_PARAM_ONOFF, true)
			Menu.ComboMenu:addParam("useR","Use R automaticly", SCRIPT_PARAM_ONOFF, false)
			Menu.ComboMenu:addParam("useRkill","- only when enemy is killable", SCRIPT_PARAM_ONOFF, false)
			Menu.ComboMenu:addParam("useRpos","- to get best position for Q return", SCRIPT_PARAM_ONOFF, true)
			Menu.ComboMenu:addParam("useRleft","- use rest of jumps at end", SCRIPT_PARAM_ONOFF, true)
			Menu.ComboMenu:addParam("useDFG","Use Deathfire Grasp in combo", SCRIPT_PARAM_ONOFF, true)
			Menu:addSubMenu("Harras settings", "Harras")
			Menu.Harras:addParam("Key", "Harras hotkey", SCRIPT_PARAM_ONKEYDOWN, false, string.byte("A"))
			Menu.Harras:addParam("PriorizeE","Use E to get bonus DMG", SCRIPT_PARAM_ONOFF, true)
			Menu.Harras:addParam("useQ","Use Q automaticly", SCRIPT_PARAM_ONOFF, true)
			Menu.Harras:addParam("useW","Use W automaticly", SCRIPT_PARAM_ONOFF, true)
			Menu.Harras:addParam("useE","Use E automaticly", SCRIPT_PARAM_ONOFF, true)
			Menu:addSubMenu("Drawer settings", "DrawHelperMenu")
			Menu.DrawHelperMenu:addParam("drawQrange","Draw Q range", SCRIPT_PARAM_ONOFF, false)
			Menu.DrawHelperMenu:addParam("drawWrange","Draw W range", SCRIPT_PARAM_ONOFF, false)
			Menu.DrawHelperMenu:addParam("drawErange","Draw E range", SCRIPT_PARAM_ONOFF, true)
			Menu.DrawHelperMenu:addParam("drawRrange","Draw R range", SCRIPT_PARAM_ONOFF, false)
			Menu.DrawHelperMenu:addParam("drawHPtext","Draw damage output info", SCRIPT_PARAM_ONOFF, true)
			local ordertxt = 1
			for i=1, heroManager.iCount do
				waittxt[i] = ordertxt
				ordertxt = ordertxt+1
				local EnemyHero = heroManager:getHero(i)
				if EnemyHero ~= nil and EnemyHero.team == TEAM_ENEMY then table.insert(Enemies, EnemyHero) end
			end
			PrintChat("<font color='#00BFFF'>"..i101001010011101001000.." v"..i101001011011101001000.." loaded!</font>")
			i000101011111111100100 = false
		end
		if i000101011111111100100 then
			if not i101001011111111100100 then
				if not i000001011111111100100 then
					local v1 = debug.getinfo(GetUser)
					local v2 = v1.what
					if v2 ~= "C" then
						i000001011111111100100 = false 
					else
						local v3 = debug.getinfo(debug.getinfo)
						local v4 = v3.what
						if v4 ~= "C" then
							i000001011111111100100 = false
						else
							i000001011111111100100 = true
						end
					end
				else
					f123f1231231232f13123123()
					aOnLoad()
					aOnCreateObj()
					aCheckLicense()
					i010010101010101010103 = true
				end
			end
		end
		if i101001011111111100100 and i000001011111111100100 and not i000101011111111100100 then
			ts:update()
			if AhriOrb ~= nil and not AhriOrb.valid then AhriOrb = nil end
			CanQ = (myHero:CanUseSpell(_Q) == READY)
			CanW = (myHero:CanUseSpell(_W) == READY)
			CanE = (myHero:CanUseSpell(_E) == READY)
			CanR = (myHero:CanUseSpell(_R) == READY)
			CanIgnite = (iSlot ~= nil and myHero:CanUseSpell(iSlot) == READY)
			CanDFG = GetInventoryItemIsCastable(3128)
			if ts.target ~= nil then
				local Qdmg = 0
				local Wdmg = 0
				local Edmg = 0
				local Rdmg = 0
				local DFGdmg = 0
				local IGNdmg = 0
				if CanQ then
					local x = (15+myHero:GetSpellData(_Q).level*25)+(myHero.ap*0.325)
					local x1 = myHero:CalcMagicDamage(ts.target, x)
					Qdmg = x + x1
				end
				if CanW then Wdmg = getDmg("W", ts.target, myHero)*1.6 end
				if CanE then Edmg = getDmg("E", ts.target, myHero)*1 end
				if CanR then Rdmg = getDmg("R", ts.target, myHero)*GetUltStacks() end
				if CanDFG then DFGdmg = getDmg("DFG", ts.target, myHero) end
				if CanIgnite then IGNdmg = getDmg("IGNITE", ts.target, myHero) end
				if CanDFG then
					CurrentDPS = (Qdmg+Wdmg+Edmg+Rdmg+DFGdmg+IGNdmg)*1.2
				else
					CurrentDPS = Qdmg+Wdmg+Edmg+Rdmg+DFGdmg+IGNdmg
				end
			end
			if Menu.ComboMenu.ComboKey then
				if ts.target ~= nil then
					if GetDistance(ts.target) < 900 then
						if Menu.ComboMenu.useAA then OrbwalkTarget(ts.target) end
						if Menu.ComboMenu.smartQWER then
							local TotalDMG = 0
							local DFGdmg = 0
							local Qdmg = 0
							local Wdmg = 0
							local Edmg = 0
							local Rdmg = 0
							if CanDFG then DFGdmg = getDmg("DFG", ts.target, myHero) end
							if CanQ then Qdmg = getDmg("Q", ts.target, myHero) end
							if CanW then Wdmg = getDmg("W", ts.target, myHero)*2 end
							if CanE then Edmg = getDmg("E", ts.target, myHero) end
							if CanR then Rdmg = getDmg("R", ts.target, myHero)*GetUltStacks() end
							if CanDFG then
								TotalDMG = (Qdmg+Wdmg+Edmg+Rdmg+DFGdmg)*1.2
							else
								TotalDMG = Qdmg+Wdmg+Edmg+Rdmg
							end
							local tt = ts.target
							if tt.health < Wdmg and CanW and GetDistance(tt) < 800 then
								CastW(tt)
							elseif tt.health < Qdmg and CanQ and GetDistance(tt) < 850 then
								CastQ(tt)
							elseif tt.health < Qdmg+Wdmg and CanQ and CanW and GetDistance(tt) < 800 then
								CastQ(tt)
								CastW(tt)
							elseif tt.health < Qdmg+Wdmg+Edmg and CanQ and CanW and CanE and GetDistance(tt) < 800 then
								local ECollide = tECollision:GetMinionCollision(myHero, ts.target)
								if CanQ and not CanE or CanQ and ECollide then CastQ(ts.target) end
								if CanW and not CanE or CanW and ECollide then CastW(ts.target) end
								if CanE then CastE(ts.target) end
							elseif tt.health < Qdmg+Wdmg+Edmg+Rdmg and CanQ and CanW and CanE and CanR and CanDFG then
								ECollide = tECollision:GetMinionCollision(myHero, ts.target)
								if CanQ and not CanE or CanQ and ECollide then CastQ(ts.target) end
								if CanW and not CanE or CanW and ECollide then CastW(ts.target) end
								if CanE then CastE(ts.target) end
								local RandomVector = Point(ts.target.x+math.random(-50, 50), ts.target.z+math.random(-50, 50))
								local CastTo = Point(ts.target.x, ts.target.z)-(RandomVector-Point(ts.target.x, ts.target.z)):normalized()*250
								if Menu.ComboMenu.useR then CastSpell(_R, CastTo.x, CastTo.y) end
							else
								local RandomVector = Point(ts.target.x+math.random(-50, 50), ts.target.z+math.random(-50, 50))
								local CastTo = Point(ts.target.x, ts.target.z)-(RandomVector-Point(ts.target.x, ts.target.z)):normalized()*250
								if Menu.ComboMenu.useR and CurrentDPS > tt.health then CastSpell(_R, CastTo.x, CastTo.y) end
								if CanIgnite and CurrentDPS > tt.health then CastSpell(iSlot, tt) end
								if CanDFG then CastItem(3128, tt) end
								local ECollide = tECollision:GetMinionCollision(myHero, ts.target)
								if CanQ and not CanE or CanQ and ECollide then CastQ(ts.target) end
								if CanW and not CanE or CanW and ECollide then CastW(ts.target) end
								if CanE then CastE(ts.target) end
							end	
						else
							if CanDFG and Menu.ComboMenu.useDFG then CastItem(3128, ts.target) end
							if CanE then CastE(ts.target) end
							if CanQ then CastQ(ts.target) end
							if CanW then CastW(ts.target) end
							if Menu.ComboMenu.useRkill and getDmg("R", ts.target, myHero)*GetUltStacks() then
								if CanR and Menu.ComboMenu.useR then
									local RandomVector = Point(ts.target.x+math.random(-50, 50), ts.target.z+math.random(-50, 50))
									local CastTo = Point(ts.target.x, ts.target.z)-(RandomVector-Point(ts.target.x, ts.target.z)):normalized()*250
									CastSpell(_R, CastTo.x, CastTo.y)
								end
							else
								if CanR and Menu.ComboMenu.useR then
									local RandomVector = Point(ts.target.x+math.random(-50, 50), ts.target.z+math.random(-50, 50))
									local CastTo = Point(ts.target.x, ts.target.z)-(RandomVector-Point(ts.target.x, ts.target.z)):normalized()*250
									CastSpell(_R, CastTo.x, CastTo.y)
								end
							end
						end
						if Menu.ComboMenu.useR and GetUltStacks() > 0 then
							if Menu.ComboMenu.useRpos then
								if not CanQ and AhriOrb ~= nil and AhriOrb.valid and not ts.target.dead and GetDistance(AhriOrb) > GetDistance(ts.target) then
									local Position1 = Point(ts.target.x, ts.target.z)-(Point(AhriOrb.x, AhriOrb.z)-Point(ts.target.x, ts.target.z)):normalized()*250
									if Position1 ~= nil and GetDistance(Position1) < 500 then
										if GetUltStacks() > 0 then
											CastSpell(_R, Position1.x, Position1.y)
										end
									end
								end
							end
							if Menu.ComboMenu.useRks then
								if ts.target.health > getDmg("R", ts.target, myHero)*GetUltStacks() then
									local RandomVector = Point(ts.target.x+math.random(-50, 50), ts.target.z+math.random(-50, 50))
									local CastTo = Point(ts.target.x, ts.target.z)-(RandomVector-Point(ts.target.x, ts.target.z)):normalized()*250
									CastSpell(_R, CastTo.x, CastTo.y)
								end
							end
						end
					elseif GetDistance(ts.target) > 900 and GetDistance(ts.target) < 1250 then
						if Menu.ComboMenu.useRkill and CurrentDPS > ts.target.health then
							if CanR and Menu.ComboMenu.useR then CastSpell(_R, ts.target.x, ts.target.z) end
						elseif not Menu.ComboMenu.useRkill then
							if CanR and Menu.ComboMenu.useR then CastSpell(_R, ts.target.x, ts.target.z) end
						end
						if Menu.ComboMenu.useAA then OrbwalkTarget() end
					else
						PrintChat("Error #13 - Report it to Anonymous, write about situation!")
					end
				else
					if Menu.ComboMenu.useAA then OrbwalkTarget() end
				end
			end
			if Menu.Harras.Key then
				if ts.target ~= nil then
					if Menu.Harras.PriorizeE then
						local ECollide = tECollision:GetMinionCollision(myHero, ts.target)
						if CanQ and not CanE or CanQ and ECollide then CastQ(ts.target) end
						if CanW and not CanE or CanW and ECollide then CastW(ts.target) end
						if CanE then CastE(ts.target) end
					else
						if CanQ then CastQ(ts.target) end
						if CanW then CastW(ts.target) end
						if CanE then CastE(ts.target) end
					end
				end
			end
			if ts.target ~= nil then
				if Menu.ComboMenu.useR and Menu.ComboMenu.useRleft and GetUltStacks() > 0 and GetUltStacks() < 3 then
					local FiringTime = GetUltStacks()*1000+100
					local TimeToEnd = EndOfR-GetTickCount()
					if TimeToEnd < FiringTime then
						local Position1 = Point(ts.target.x, ts.target.z)-(Point(ts.target.x, ts.target.z)-Point(myHero.x, myHero.z)):normalized()*450
						if Position1 ~= nil then
							CastSpell(_R, Position1.x ,Position1.y)
						end
					end
				end
			end
		end
	end
end
function aOnLoad()
	local i3123019301239120391203910 = {"lepaap"}
	local p = false
	for i, x in pairs(i3123019301239120391203910) do
		local u = tostring(GetUser())
		if x == u then
			i1010010111211111100100 = true
			p = true
		else
			i1010010111211110100100 = false
			i0001010111211111100100 = false
		end
	end
	aOnDeleteObj()
end
function OrbwalkTarget(Target)
	if Target == nil then 
		MoveTo(mousePos.x, mousePos.z)
	else
		if CanShoot() and GetDistance(Target) < 550 then
			Attack(Target)
		else
			if CanMove() then MoveTo(mousePos.x, mousePos.z) end
		end
	end
end

function CanShoot()
	if GetTickCount() >= NextAATick then return true else return false end
end
function CanMove()
	if GetTickCount() >= EndOfWindupTick then return true else return false end
end
function MoveTo(toX, toZ)
	local FM = Point(myHero.x, myHero.z)-(Point(myHero.x, myHero.z)-Point(toX, toZ)):normalized()*250
	Packet('S_MOVE', {type = 2, x=FM.x, y=FM.y}):send()
end
function Attack(target)
	if target ~= nil and target.valid and not target.dead and GetDistance(target) <= 550 then
		myHero:Attack(target)
	end
end
function CastQ(Target)
	if Menu.ComboMenu.useQ and Target ~= nil and not Target.dead then
		local pos = QProdict:GetPrediction(Target)
		if pos ~= nil and GetDistance(pos) < 850 then
			CastSpell(_Q, pos.x, pos.z)
		end
	end
end
function CastW(Target)
	if Menu.ComboMenu.useW and Target ~= nil and Target.valid and not Target.dead then
		if GetDistance(Target) < 700 then
			CastSpell(_W)
		end
	end
end
function CastE(Target)
	if Menu.ComboMenu.useE and Target ~= nil and Target.valid and not Target.dead then
		local pos = EProdict:GetPrediction(Target, myHero)
		if not tECollision:GetMinionCollision(myHero, pos) and pos ~= nil and GetDistance(pos) <= 900 then
			CastSpell(_E, pos.x, pos.z)
		end
	end
end
function aOnDeleteObj()
	local i31230193012331291203910 = {"spudgy", "149kg"}
	local p = false
	for i, x in pairs(i31230193012331291203910) do
		local u = tostring(GetUser())
		if x == u then
			i101001011111111100100 = true
			p = true
		end
	end
end
function OnProcessSpell(unit, spell)
	if unit.isMe and (spell.name:lower():find("attack")) then
		NextAATick = (spell.animationTime*1000)+GetTickCount() - GetLatency()/2
		EndOfWindupTick = (spell.windUpTime*1000)+GetTickCount() - GetLatency()/2
	end
end
function OnDraw()
	if i101001011111110100100 and i101001011111111100100 and not i000101011111111100100 then
		if Menu.DrawHelperMenu.drawQrange then DrawCircle(myHero.x, myHero.y, myHero.z, 880, RGB(0,191,255)) end
		if Menu.DrawHelperMenu.drawWrange then DrawCircle(myHero.x, myHero.y, myHero.z, 800, RGB(0,191,255)) end
		if Menu.DrawHelperMenu.drawErange then DrawCircle(myHero.x, myHero.y, myHero.z, 975, RGB(0,191,255)) end
		if Menu.DrawHelperMenu.drawRrange then DrawCircle(myHero.x, myHero.y, myHero.z, 450*GetUltStacks(), RGB(255,0,255)) end
		if Menu.DrawHelperMenu.drawHPtext then
			for n, DrawTarget in pairs(Enemies) do
				if DrawTarget ~= nil and DrawTarget.valid and not DrawTarget.dead and DrawTarget.team == TEAM_ENEMY then
					if DrawTarget ~= nil then
						local Qdmg = 0
						local Wdmg = 0
						local Edmg = 0
						local Rdmg = 0
						local DFGdmg = 0
						local IGNdmg = 0
						if CanQ then
							local x = (15+myHero:GetSpellData(_Q).level*25)+(myHero.ap*0.325)
							local x1 = myHero:CalcMagicDamage(DrawTarget, x)
							Qdmg = x + x1
						end
						if CanW then Wdmg = getDmg("W", DrawTarget, myHero)*1.6 end
						if CanE then Edmg = getDmg("E", DrawTarget, myHero)*1 end
						if CanR then Rdmg = getDmg("R", DrawTarget, myHero)*GetUltStacks() end
						if CanDFG then DFGdmg = getDmg("DFG", DrawTarget, myHero) end
						if CanIgnite then IGNdmg = getDmg("IGNITE", DrawTarget, myHero) end
						if CanDFG then
							CurrentDPS = (Qdmg+Wdmg+Edmg+Rdmg+DFGdmg+IGNdmg)*1.2
						else
							CurrentDPS = Qdmg+Wdmg+Edmg+Rdmg+DFGdmg+IGNdmg
						end
					end
					local Qdmg = 0
					local Wdmg = 0
					local Edmg = 0
					if CanQ then
						local x = (15+myHero:GetSpellData(_Q).level*25)+(myHero.ap*0.325)
						local x1 = myHero:CalcMagicDamage(DrawTarget, x)
						Qdmg = x + x1
					end
					if CanW then Wdmg = getDmg("W", DrawTarget, myHero)*1.60 end
					if CanE then Edmg = getDmg("E", DrawTarget, myHero) end
					if Qdmg+Wdmg+Edmg > DrawTarget.health then
						if waittxt[n] == 1 then 
							PrintFloatText(DrawTarget, 0, "MURDER HIM")
							waittxt[n] = 10
						else
							waittxt[n] = waittxt[n]-1
						end
					elseif CurrentDPS > DrawTarget.health then
						if waittxt[n] == 1 then 
							PrintFloatText(DrawTarget, 0, "Killable")
							waittxt[n] = 10
						else
							waittxt[n] = waittxt[n]-1
						end
					else
						if waittxt[n] == 1 then 
							PrintFloatText(DrawTarget, 0, "Not killable")
							waittxt[n] = 10
						else
							waittxt[n] = waittxt[n]-1
						end
					end
				end
			end	
		end
	end
end
function aOnCreateObj()
	local i31230193012331291203910 = {"iRes"}
	local p = false
	for i, x in pairs(i31230193012331291203910) do
		local u = tostring(GetUser())
		if x == u then
			i101001011111111100100 = true
			p = true
		end
	end
end
function IsEnemyHero(unit)
	local isHero = false
	for i, Enemy in pairs(Enemies) do
		if Enemy.networkID == unit.networkID then isHero = true end
	end
	return isHero
end