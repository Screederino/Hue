<?php exit() ?>--by sida 81.170.70.121

--[[
		Sida's Auto Reborn
]]

local AutoCarryGlobal = {}
local fma = false
AutoCarryGlobal.Data = {}
_G.AutoCarry = AutoCarryGlobal.Data

local DoneInit = false

function MainTick()
	if Keys.LastHit then 
		Minions:LastHit() 
	end

	if Keys.AutoCarry then
		if Crosshair.TargetLock then
			Items:UseAll(Crosshair.Attack_Crosshair.target) 
			Skills:CastAll(Crosshair:GetTarget()) 
			Orbwalker:Orbwalk(Crosshair.TargetLock)
		else
			Items:UseAll(Crosshair.Attack_Crosshair.target) 
			Skills:CastAll(Crosshair:GetTarget()) 
			if Orbwalker:CanOrbwalkTarget(Crosshair.Attack_Crosshair.target) then
				Orbwalker:Orbwalk(Crosshair.Attack_Crosshair.target)
			elseif Structures:CanOrbwalkStructure() then
				Orbwalker:OrbwalkIgnoreChecks(Structures:GetTargetStructure())
			else
				Orbwalker:Orbwalk(Jungle:GetFocusedMonster())
			end
		end
	end

	if Keys.LaneClear then 
		if Crosshair.TargetLock then
			Skills:CastAll(Crosshair.TargetLock) 
			Orbwalker:Orbwalk(Crosshair.TargetLock)
		else
			Skills:CastAll(Crosshair:GetTarget()) 
			if LaneClearMenu.MinionPriority and Orbwalker:CanOrbwalkTarget(Minions.KillableMinion) and not ConfigurationMenu.SupportMode then
				Orbwalker:Orbwalk(Minions.KillableMinion)
			elseif LaneClearMenu.AttackEnemies and Orbwalker:CanOrbwalkTarget(Crosshair.Attack_Crosshair.target) then
				Items:UseAll(Crosshair.Attack_Crosshair.target)
				Orbwalker:Orbwalk(Crosshair.Attack_Crosshair.target)
			elseif Orbwalker:CanOrbwalkTarget(Jungle:GetAttackableMonster()) then
				Orbwalker:Orbwalk(Jungle:GetAttackableMonster())
			else
				Minions:LaneClear()
			end
		end
	end

	if Keys.MixedMode then 	
		if Crosshair.TargetLock then	
			Skills:CastAll(Crosshair.TargetLock)
			Orbwalker:Orbwalk(Crosshair.TargetLock)
		else
			Skills:CastAll(Crosshair:GetTarget()) 
			if MixedModeMenu.MinionPriority and Orbwalker:CanOrbwalkTarget(Minions.KillableMinion) and not ConfigurationMenu.SupportMode then
				Orbwalker:Orbwalk(Minions.KillableMinion)
			elseif Orbwalker:CanOrbwalkTarget(Crosshair.Attack_Crosshair.target) then
				Items:UseAll(Crosshair.Attack_Crosshair.target)
				Orbwalker:Orbwalk(Crosshair.Attack_Crosshair.target)
			else
				Minions:LastHit()
			end
		end
	end
end

if AdvancedCallback then
	AdvancedCallback:bind('OnLoseVision', function(unit) end)
	AdvancedCallback:bind('OnGainVision', function(unit) end)
	AdvancedCallback:bind('OnDash', function(unit) end)
	AdvancedCallback:bind('OnGainBuff', function(unit, buff) end)
	AdvancedCallback:bind('OnLoseBuff', function(unit, buff) end)
	AdvancedCallback:bind('OnUpdateBuff', function(unit, buff) end)
end

function HMA()
	if _G.MMA_AttackAvailable or _G.MMA_AbleToMove or _G.MMA_NextAttackAvailability or _G.MMA_Target or _G.MMA_ForceTarget or _G.MMA_ConsideredTarget or _G.MMA_Loaded then
		fma = true
		return true
	end
end

MODE_AUTOCARRY = 0
MODE_MIXEDMODE = 1
MODE_LASTHIT = 2
MODE_LANECLEAR = 3
AutoCarry.MODE_AUTOCARRY = 0
AutoCarry.MODE_MIXEDMODE = 1
AutoCarry.MODE_LASTHIT = 2
AutoCarry.MODE_LANECLEAR = 3

--[[
		Orbwalker Class
						]]
class '_Orbwalker' Orbwalker = nil

STAGE_SHOOT = 0
STAGE_MOVE = 1
STAGE_SHOOTING = 2
AutoCarry.STAGE_SHOOT = 0
AutoCarry.STAGE_MOVE = 1
AutoCarry.STAGE_SHOOTING = 2
RegisteredOnAttacked = {}

function _Orbwalker:__init()
	self.LastAttack = 0
	self.LastWindUp = 0
	self.LastAttackCooldown = 0
	self.AttackCompletesAt = 0
	self.AfterAttackTime = 0
	self.AttackBufferMax = 400
	self.OrbwalkLocationOverride = nil
	self.LastAttackedPosition = {x = myHero.x, z = myHero.z}
	self.Allies = {}

	self.LastAttackSpeed = myHero.attackSpeed
	self.LowestAttackSpeed = myHero.attackSpeed

	AddTickCallback(function() self:_OnTick() end)
	AddProcessSpellCallback(function(Unit, Spell) self:_OnProcessSpell(Unit, Spell) end)
	AddAnimationCallback(function(Unit, Animation) self:_OnAnimation(Unit, Animation) end)
	AddSendPacketCallback(function(Packet) self:_OnSendPacket(Packet) end)
	
end

--[[ Callbacks ]]
function _Orbwalker:_OnTick()
	if not self:CanShoot() and self:CanMove() and not self.DoneOnAttacked then
		self.AfterAttackTime = self.LastAttack + self.LastWindUp + self.AttackBufferMax
		self:_OnAttacked()
	end
end

function _Orbwalker:_OnSendPacket(p)
	-- local packet = Packet(p)
	-- if packet:get('name') == 'S_MOVE' then
 --  		local Unit = objManager:GetObjectByNetworkId(packet:get('sourceNetworkId'))
 --  		if Unit.isMe then
 --  			if GetTickCount() < Orbwalker.AttackCompletesAt then
 --  				self:ResetAttackTimer()
 --  			end
 --  		end
 --  	end
end

function _Orbwalker:_OnProcessSpell(Unit, Spell)
	if Unit.isMe then
		if Data:IsAttack(Spell) then
			self.LastAttack = GetTickCount() - GetLatency() / 2
			self.LastWindUp = Spell.windUpTime * 1000
			self.LastAttackCooldown = Spell.animationTime * 1000
			self.AttackCompletesAt = self.LastAttack + self.LastWindUp
			self.LastAttackSpeed = myHero.attackSpeed
			self.LastAttackedPosition = {x = myHero.x, z = myHero.z}
			self.DoneOnAttacked = false
			MyHero.DonePreAttack = false
		elseif Data:IsResetSpell(Spell) then
			self:ResetAttackTimer()
		end
	elseif Unit.type == myHero.type and Unit.team == myHero.team then
		if Data:IsAttack(Spell) then
			local _Row = {WindUp = Spell.windUpTime * 1000, Cooldown = Spell.animationTime * 1000}
			self.Allies[Unit.networkID] = _Row
		end
	end
end

function _Orbwalker:_OnAnimation(Unit, Animation)
	if GetTickCount() < Orbwalker.AttackCompletesAt and Unit.isMe and (Animation == "Run" or Animation == "Idle") then
		self:ResetAttackTimer()
	end
end

function _Orbwalker:ResetAttackTimer()
	self.LastAttack = GetTickCount() - GetLatency() / 2 - self.LastAttackCooldown
end

function _Orbwalker:Orbwalk(Target)

end

function _Orbwalker:OrbwalkToPosition(Target, Position)
	if self:CanOrbwalkTarget(Target) then
		if self:CanShoot() then
			MyHero:Attack(Target)
		elseif self:CanMove()  then
			MyHero:Move(Position)
		end
	else
		MyHero:Move(Position)
	end
end

function _Orbwalker:OrbwalkIgnoreChecks(target)
	if target and self:CanShoot() then
		MyHero:Attack(target)
	elseif not self:CanShoot() then
		MyHero:Move()
	end
end

function _Orbwalker:CanMove()
	return (GetTickCount() + (GetLatency() / 2) - ModesMenu.aaDelay > self.LastAttack + self.LastWindUp + 20)
end

function _Orbwalker:CanShoot()
	return (GetTickCount() + (GetLatency() / 2) > self.LastAttack + (self.LastAttackCooldown * self:GetAttackSlowModifier()))
end

function _Orbwalker:GetHitboxRadius(Unit)
	if Unit ~= nil then 
		return Helper:GetDistance(Unit.minBBox, Unit.maxBBox) / 2
	end
end

function _Orbwalker:CanOrbwalkTarget(Target)
	if ValidTarget(Target) then
		if Target.type == myHero.type then
			if Helper:GetDistance(Target) - Data:GetGameplayCollisionRadius(Target.charName) - self:GetScalingRange(Target) < MyHero.TrueRange then
				return true
			end
		else
			if Helper:GetDistance(Target) - Data:GetGameplayCollisionRadius(Target.charName) + 20 < MyHero.TrueRange then
				return true
			end
		end
	end
	return false
end

function _Orbwalker:CanOrbwalkTargetCustomRange(Target, Range)
	if ValidTarget(Target) then
		if Target.type == myHero.type then
 			if Helper:GetDistance(Target) - Data:GetGameplayCollisionRadius(Target.charName) - self:GetScalingRange(Target) < Range + MyHero.GameplayCollisionRadius + MyHero:GetScalingRange() then
				return true
			end
		else
			if Helper:GetDistance(Target) - Data:GetGameplayCollisionRadius(Target.charName) + 20 < Range + MyHero.GameplayCollisionRadius + MyHero:GetScalingRange() then
				return true
			end
		end
	end
	return false
end

function _Orbwalker:CanOrbwalkTargetFromPosition(Target, Position)
	if ValidTarget(Target) then
		if Target.type == myHero.type then
			if Helper:GetDistance(Target, Position) - Data:GetGameplayCollisionRadius(Target.charName) - self:GetScalingRange(Target) < MyHero.TrueRange then
				return true
			end
		else
			if Helper:GetDistance(Target, Position) - Data:GetGameplayCollisionRadius(Target.charName) < MyHero.TrueRange then
				return true
			end
		end
	end
	return false
end

function _Orbwalker:IsAfterAttack()
	return GetTickCount() + (GetLatency() / 2) < self.AfterAttackTime
end

function _Orbwalker:GetScalingRange(Target)
	if Target.type == myHero.type and Target.team ~= myHero.team then
		local scale = Data:GetOriginalHitBox(Target)
		return (scale and (Helper:GetDistance(Target.minBBox, Target.maxBBox) - Data:GetOriginalHitBox(Target)) / 2 or 0)
	end
	return 0
end

function _Orbwalker:GetAnimationOverdrive()
	local Amount = 0
	-- if OverdriveMenu.Enabled then
	-- 	Amount = OverdriveMenu.AnimationOverdrive - (myHero.attackSpeed * 10)
	-- end
	return Amount > 0 and Amount or 0
end

function _Orbwalker:GetAttackSlowModifier()
	return self.LastAttackSpeed / myHero.attackSpeed
end

function _Orbwalker:GetNextAttackTime()
	return self.LastAttack + (self.LastAttackCooldown * self:GetAttackSlowModifier())
end

function _Orbwalker:IsShooting()
	return not self:CanMove() and not self:CanShoot()
end

function _Orbwalker:AttackOnCooldown()
	return GetTickCount() < self:GetNextAttackTime()
end

function _Orbwalker:AttackReady()
	return self:CanShoot()
end

function _Orbwalker:GetAllyTimeToAttack(Ally, Target)
	local ProjSpeed = Data:GetProjectileSpeed(Ally.charName)
	local WindUp = Orbwalker.Allies[Ally.networkID]
	WindUp = WindUp and WindUp.WindUp or 0
	
	return (Helper:GetDistance(Ally, Target) / ProjSpeed) + WindUp
end

function _Orbwalker:IsChanelling()
	if ChampionBuffs.KatarinaUlt then
		return true
	end
end

function RegisterOnAttacked(func)
	table.insert(RegisteredOnAttacked, func)
end

function _Orbwalker:_OnAttacked()
	for _, func in pairs(RegisteredOnAttacked) do
		func()
	end
	self.DoneOnAttacked = true
end

function _Orbwalker:OverrideOrbwalkLocation(Position)
	self.OrbwalkLocationOverride = Position
end

--[[					
		_MyHero Class
						]]

class '_MyHero' MyHero = nil

function _MyHero:__init()
	self.Range = myHero.range
	self.HitBox = Helper:GetDistance(myHero.minBBox)
	self.GameplayCollisionRadius = Data:GetGameplayCollisionRadius(myHero.charName)
	self.TrueRange = self.Range + self.GameplayCollisionRadius
	self.IsMelee = myHero.range < 300
	self.MoveDistance = 480
	self.LastHitDamageBuffer = -15 --TODO
	self.StartAttackSpeed = 0.665
	self.ChampionAdditionalLastHitDamage = 0
	self.ItemAdditionalLastHitDamage = 0
	self.MasteryAdditionalLastHitDamage = 0
	self.Team = myHero.team == 100 and "Blue" or "Red"
	self.ProjectileSpeed = Data:GetProjectileSpeed(myHero.charName)
	self.LastMoved = 0
	self.MoveDelay = 50
	self.CanMove = true
	self.CanAttack = true
	self.CanOrbwalk = true
	self.InStandZone = false
	self.HasStopped = false
	self.HasSpoils = false
	self.SpoilStacks = 0
	self.LastSpoil = 0
	self.IsAttacking = false
	self.Spoils = {}
	MyHero = self

	for i = 0, objManager.maxObjects do
		local Object = objManager:getObject(i)
		if Object and Helper:GetDistance(Object) < 80 and  Object.name:find("GLOBAL_Item_FoM_Charge") then
			if Object.name:find("GLOBAL_Item_FoM_Charge01") then
				self.LastSpoilCreated = 1
				self.SpoilStacks = 1
			elseif Object.name:find("GLOBAL_Item_FoM_Charge02") then
				self.LastSpoilCreated = 2
				self.SpoilStacks = 2
			elseif Object.name:find("GLOBAL_Item_FoM_Charge03") then
				self.LastSpoilCreated = 3
				self.SpoilStacks = 3
			elseif Object.name:find("GLOBAL_Item_FoM_Charge04") then
				self.LastSpoilCreated = 4
				self.SpoilStacks = 4
			end
			table.insert(self.Spoils, Object)
		end
	end

	AddTickCallback(function() self:_OnTick() end)
	AddCreateObjCallback(function(Object) self:_OnCreateObj(Object) end)
	AddDeleteObjCallback(function(Object) self:_OnDeleteObj(Object) end)
end

function _MyHero:_OnTick()
	self.TrueRange = myHero.range + self.GameplayCollisionRadius + self:GetScalingRange()
	if myHero.range ~= self.Range then 
		if myHero.range and myHero.range > 0 and myHero.range < 1500 then
			self.Range = myHero.range
			self.IsMelee = myHero.range < 300
		end
	end
	if Helper:GetDistance(mousePos) < ExtrasMenu["StandZone"..myHero.charName] then
		self.InStandZone = true
	else
		self.InStandZone = false
	end

	self.HasSpoils = self.SpoilStacks > 0
	self:CheckStopMovement()
	self.ChampionAdditionalLastHitDamage = ChampionBuffs:GetBonusDamage()
end

function _MyHero:_OnCreateObj(Object)
	if Helper:GetDistance(Object) < 80 and Object.name:find("GLOBAL_Item_FoM_Charge") then
		if Object.name:find("GLOBAL_Item_FoM_Charge01") then
			self.LastSpoilCreated = 1
			self.SpoilStacks = 1
		elseif Object.name:find("GLOBAL_Item_FoM_Charge02") then
			self.LastSpoilCreated = 2
		elseif Object.name:find("GLOBAL_Item_FoM_Charge03") then
			self.LastSpoilCreated = 3
		elseif Object.name:find("GLOBAL_Item_FoM_Charge04") then
			self.LastSpoilCreated = 4
			self.SpoilStacks = 4
		end
		table.insert(self.Spoils, Object)
	end
end

function _MyHero:_OnDeleteObj(Object)
	for i, Spoil in pairs(self.Spoils) do
		if Object and Object == Spoil then
			if Object.name:find("GLOBAL_Item_FoM_Charge01") then
				if self.LastSpoilCreated == 1 then
					self.SpoilStacks = 0
				else
					self.SpoilStacks = 2
				end
			elseif Object.name:find("GLOBAL_Item_FoM_Charge02") then
				if self.LastSpoilCreated == 1 then
					self.SpoilStacks = 1
				else
					self.SpoilStacks = 3
				end
			elseif Object.name:find("GLOBAL_Item_FoM_Charge03") then
				if self.LastSpoilCreated == 4 then
					self.SpoilStacks = 4
				else
					self.SpoilStacks = 2
				end
			elseif Object.name:find("GLOBAL_Item_FoM_Charge04") then
				self.SpoilStacks = 3
			end
			table.remove(self.Spoils, i)
		end
		
	end
end

function _MyHero:GetScalingRange()
	local scale = Data:GetOriginalHitBox(myHero)
	return (scale and (Helper:GetDistance(myHero.minBBox, myHero.maxBBox) - Data:GetOriginalHitBox(myHero)) / 2 or 0)
end

function _MyHero:SetProjectileSpeed(Speed)
	self.ProjectileSpeed = Speed
end

function _MyHero:GetTimeToHitTarget(Target)
	-- local proj = MyHero.ProjectileSpeed
	-- proj = proj - 2
	-- local lastWind = Orbwalker.LastWindUp --* (1 + ((proj * -1)*0.6))

	-- local AttackTime = (Helper:GetDistance(Target) / self.ProjectileSpeed) + (Helper.Latency / 2) + lastWind --Orbwalker.LastWindUp
	
	-- local NextAttack = Orbwalker:GetNextAttackTime()
	-- if NextAttack <= GetTickCount() then
	-- 	NextAttack = GetTickCount()
	-- end

	-- return AttackTime + NextAttack

	if self.IsMelee then
		return GetTickCount() + Orbwalker.LastWindUp + GetLatency() / 2
	else
		return GetTickCount() + (Helper:GetDistance(Target) - self.GameplayCollisionRadius) / self.ProjectileSpeed + Orbwalker.LastWindUp + GetLatency() / 2
	end
end

function _MyHero:GetTotalAttackDamageAgainstTarget(Target, LastHit)
	local MyDamage = myHero.totalDamage --:CalcDamage(Target, myHero.totalDamage)
	if LastHit then
		MyDamage = self:GetMasteryAdditionalLastHitDamage(MyDamage, Target)
		MyDamage = MyDamage + self.ChampionAdditionalLastHitDamage
		--MyDamage = MyDamage + self.ItemAdditionalLastHitDamage
	end
	return MyDamage
end

function _MyHero:GetMasteryAdditionalLastHitDamage(Damage, Target)
	if not ConfigMenu then return Damage end

	local armorPen = 0
	local armorPenPercent = 0
	local magicPen = 0
	local magicPenPercent = 0
	local magicDamage = 0
	local physDamage = _Damage
	local dmgReductionPercent = 0

	local totalDamage = physDamage

	if ConfigMenu.ArcaneBlade then
		magicDamage = myHero.ap * .05
	end

	if ConfigMenu.DevastatingStrike then
		armorPenPercent = .06
	end

	if ConfigMenu.DoubleEdgedSword then
		physDamage = myHero.range < 400 and physDamage*1.02 or (physDamage*1.015)
		magicDamage = myHero.range < 400 and magicDamage*1.02 or (magicDamage*1.015)
	end

	if ConfigMenu.Butcher then
		physDamage = physDamage + 2
	end

	return ((physDamage * (100/(100 + target.armor * (1-armorPenPercent)))  
	 + magicDamage * (100/(100 + target.magicArmor * (1-magicPenPercent))) ) * (1-dmgReductionPercent))
end

function _MyHero:Move(Position)
	if self:HeroCanMove() and not Helper:IsEvading() and not Orbwalker:IsShooting() and Orbwalker:CanMove() and not Orbwalker:IsChanelling() then

		local _Position = Position and Position or mousePos
		_Position = Orbwalker.OrbwalkLocationOverride or _Position

		if Helper.Tick > self.LastMoved + self.MoveDelay then
			Streaming:OnMove()
			local Distance = self.MoveDistance + Helper.Latency / 10
			if self.IsMelee and Crosshair.Attack_Crosshair.target and Crosshair.Attack_Crosshair.target.type == myHero.type and 
				MeleeMenu.MeleeStickyRange > 0 and Helper:GetDistance(Crosshair.Attack_Crosshair.target) - Data:GetGameplayCollisionRadius(Crosshair.Attack_Crosshair.target) < MeleeMenu.MeleeStickyRange and 
				Orbwalker:CanOrbwalkTarget(Crosshair.Attack_Crosshair.target) then
					return
			elseif Helper:GetDistance(_Position) < Distance and Helper:GetDistance(_Position) > 100 then
				Distance = Helper:GetDistance(_Position)
			end

			local MoveSqr = math.sqrt((_Position.x - myHero.x) ^ 2 + (_Position.z - myHero.z) ^ 2)
			local MoveX = myHero.x + Distance * ((_Position.x - myHero.x) / MoveSqr)
			local MoveZ = myHero.z + Distance * ((_Position.z - myHero.z) / MoveSqr)

			myHero:MoveTo(MoveX, MoveZ)
			self.LastMoved = Helper.Tick
			self.HasStopped = false
		end
	end
end

function _MyHero:Attack(target)
	if not self:HeroCanAttack() then
		MyHero:Move()
		return
	end
	if self.CanAttack and not Helper:IsEvading() then
		if target.type ~= myHero.type then 
			MuramanaOff()
		end

		if not self.DonePreAttack then
			for _, func in pairs(Plugins.RegisteredPreAttack) do
				func(target)
			end
			self.DonePreAttack = true
		end
		myHero:Attack(target)
		Orbwalker.LastEnemyAttacked = target
	end
end

function _MyHero:MovementEnabled(canMove)
	self.CanMove = canMove
end

function _MyHero:AttacksEnabled(canAttack)
	self.CanAttack = canAttack
end

function _MyHero:OrbwalkingEnabled(canOrbwalk)
	self.CanOrbwalk = canOrbwalk
end

function _MyHero:HeroCanAttack()
	if not AutoCarryMenu.Attacks and Keys.AutoCarry then
		return false
	elseif not LastHitMenu.Attacks and Keys.LastHit then
		return false
	elseif not MixedModeMenu.Attacks and Keys.MixedMode then
		return false
	elseif not LaneClearMenu.Attacks and Keys.LaneClear then
		return false
	end
	return true
end

function _MyHero:HeroCanMove()
	if self.InStandZone or not self.CanMove then
		return false
	elseif not AutoCarryMenu.Movement and Keys.AutoCarry then
		return false
	elseif not LastHitMenu.Movement and Keys.LastHit then
		return false
	elseif not MixedModeMenu.Movement and Keys.MixedMode then
		return false
	elseif not LaneClearMenu.Movement and Keys.LaneClear then
		return false
	end
	return true
end

function _MyHero:CheckStopMovement()
	if not MyHero:HeroCanMove() and not self.HasStopped then
		myHero:HoldPosition()
		self.HasStopped = true
	end
end

--[[ 
		_AntiCancel Class
	]]

class '_AntiCancel'

function _AntiCancel:__init()
	self.BlockUntil = 0
	self.BlockNext = false
	self.Animation = nil
	self.Buff = nil

	if myHero.charName == "Katarina" then
		self.Animation = "Spell4"
		self.Buff = "katarinarsound"
	end

	AddAnimationCallback(function(Unit, Animation) self:_OnAnimation(Unit, Animation) end)
	AdvancedCallback:bind("OnLoseBuff", function(Unit, Buff) self:_OnLoseBuff(Unit, Buff) end)
	AddSendPacketCallback(function(p) self:_OnSendPacket(p) end)
end

function _AntiCancel:_OnAnimation(Unit, Animation)
	if Unit.isMe and self.Animation then
		if Animation == self.Animation then
			self.BlockUntil = GetTickCount() + 800
			self.BlockNext = true
			MyHero:MovementEnabled(false)
		end
	end
end

function _AntiCancel:_OnLoseBuff(Unit, Buff)
	if Unit.isMe and self.Buff then
		if Buff.name == self.Buff then
			MyHero:MovementEnabled(true)
		end
	end
end

function _AntiCancel:_OnSendPacket(p)
	if (self.BlockNext or GetTickCount() < self.BlockUntil) and p.header == Packet.headers.S_MOVE then
		local packet = Packet(p)
		packet:block()
		self.BlockNext = false
	end
end


--[[ 
		_Crosshair Class
	]]

class '_Crosshair' Crosshair = nil

--[[
		Initialise _Crosshair class

		damageType  	DAMAGE_PHYSICAL or DAMAGE_MAGIC
		attackRange 	Integer
		skillRange 		Integer
		targetFocused 	Boolean. Whether targets selected with left click should be focused.
		isCaster		Boolean. Whether spells should be prioritised over auto attacks.
]]

function _Crosshair:__init(damageType, attackRange, skillRange, targetFocused, isCaster)
	self.DamageType = damageType and damageType or DAMAGE_PHYSICAL
	self.AttackRange = attackRange
	self.SkillRange = skillRange
	self.TargetFocused = targetFocused
	self.TargetLock = nil
	self.IsCaster = isCaster
	self.Target = nil
	self.TargetMinion = nil
	self.Attack_Crosshair = TargetSelector(TARGET_LOW_HP_PRIORITY, 2000, DAMAGE_PHYSICAL, self.TargetFocused)
	self.Skills_Crosshair = TargetSelector(TARGET_LOW_HP_PRIORITY, skillRange, self.DamageType, self.TargetFocused)
	self.Attack_Crosshair:SetConditional(function(Hero) return self:Conditional(Hero) end)
	self.Attack_Crosshair:SetDamages(0, myHero.totalDamage, 0)
	self:ArrangePriorities()
	self.RangeScaling = true
	Crosshair = self

	self:UpdateCrosshairRange()
	self:LoadTargetSelector()

	AddTickCallback(function() self:_OnTick() end)
	AddUnloadCallback(function() self:_OnUnload() end)
end

function _Crosshair:_OnTick()
	self.Attack_Crosshair:update()

	if not ModesMenu.TargetLock or (self.TargetLock and self.TargetLock.dead) then
		self.TargetLock = nil
	elseif not self.TargetLock then
		self:SetTargetLock(self:GetTarget())
	end

	if self.Attack_Crosshair.target then
		self.Target = self.Attack_Crosshair.target
	else
		self.Skills_Crosshair:update()
		self.Target = self.Skills_Crosshair.target
	end
	if ConfigurationMenu.Focused ~= self.TargetFocused then
		self.TargetFocused = ConfigurationMenu.Focused
		self.Attack_Crosshair.targetSelected = self.TargetFocused
		self.Skills_Crosshair.targetSelected = self.targetFocused
	end
	self.TargetMinion = Minions.Target
end

function _Crosshair:_OnUnload()
	self:SaveTargetSelector()
end

function _Crosshair:GetTarget()
	if self.TargetLock then
		return self.TargetLock
	elseif ValidTarget(self.Attack_Crosshair.target) and not self.IsCaster then
		return self.Attack_Crosshair.target
	elseif ValidTarget(self.Skills_Crosshair.target) then
		return self.Skills_Crosshair.target
	end
end

function _Crosshair:HasOrbwalkTarget()
	return self and self.Target and self.Attack_Crosshair.Target and self.Target == self.Attack_Crosshair.target
end

function _Crosshair:ArrangePriorities()
	if #GetEnemyHeroes() < 5 then return end
	for _, Champion in pairs(Data.ChampionData) do
		TS_SetHeroPriority(Champion.Priority, Champion.Name)
	end
end

function _Crosshair:SetSkillCrosshairRange(Range)
	self.RangeScaling = false
	self.Skills_Crosshair.range = Range
end

function _Crosshair:UpdateCrosshairRange()
	for _, Skill in pairs(Skills.SkillsList) do
		if Skill:GetRange() > self.Skills_Crosshair.range then
			self.Skills_Crosshair.range = Skill:GetRange()
		end
	end
end

function _Crosshair:SaveTargetSelector()
	local save = GetSave("SidasAutoCarry")
	save.TargetSelectorMode = Crosshair.Attack_Crosshair.mode
	save:Save()
end

function _Crosshair:LoadTargetSelector()
	local save = GetSave("SidasAutoCarry")
	if save.TargetSelectorMode then
		Crosshair.Attack_Crosshair.mode = save.TargetSelectorMode
		Crosshair.Skills_Crosshair.mode = save.TargetSelectorMode
	end
end

function _Crosshair:Conditional(Hero)
	return Hero.team ~= myHero.team and Orbwalker:CanOrbwalkTarget(Hero) and not Data:EnemyIsImmune(Hero)
end

function _Crosshair:SetTargetLock(Target)
	self.TargetLock = Target
end

--[[
		_Minions Class
]]

class '_Minions' Minions = nil

function _Minions:__init()
	self.IncomingAttacks = {}
	self.KillableMinion = nil
	self.AlmostKillable = nil
	self.LastHitEnabled = true
	self.AlmostKillableCheck = nil
	self.MinionTargets = {}
	self.EnemyMinions = minionManager(MINION_ENEMY, 2000, myHero, MINION_SORT_HEALTH_ASC)
	self.AllyMinions = minionManager(MINION_ALLY, 2000, myHero, MINION_SORT_HEALTH_ASC)
	self.AllMinions = minionManager(MINION_ALL, 2000, myHero, MINION_SORT_HEALTH_ASC)

	AddTickCallback(function() self:_OnTick() end)
	AddCreateObjCallback(function(Object) self:_OnCreateObj(Object) end)
	AddProcessSpellCallback(function(Unit, Spell) self:_OnProcessSpell(Unit, Spell) end)
end

local updates = {}

function _Minions:_OnTick()
	self.AllMinions:update()
	--self.EnemyMinions:update()
	--self.AllyMinions:update()
	self:UpdateMinionTables()

	local Freeze = false

	for Source, Attack in pairs(self.IncomingAttacks) do
		if not Attack:StillViable(Attack) then
			self.IncomingAttacks[Source] = nil
		end
	end

	for i, Source in pairs(self.MinionTargets) do
		if not ValidTarget(Source, 2000, false) or Helper:GetDistance(Source, self.MinionTargets[i].startPos) > 5 then
			table.remove(self.MinionTargets, i)
		end
	end

	if not Orbwalker:CanOrbwalkTarget(self.KillableMinion) then
		self.KillableMinion = nil
	else
		return
	end

	if not Orbwalker:CanOrbwalkTarget(self.AlmostKillable) or self:GetNumberOfAttackingMinions(self.AlmostKillable) <= 0 then
		self.AlmostKillable = nil
	end

	local _Menu = MenuManager:GetActiveMenu()
	if _Menu and FarmMenu.Freeze and (_Menu.name ~= "sidasaclaneclear") then
		Freeze = true
	end

	self.SelectedMinion = {}

	for _, Cannon in pairs(self:GetEnemyCannons()) do
		local Killable, Almost = self:DoMinionCalc(Minion, Freeze)
		if Killable then
			self.KillableMinion = Killable
			return
		elseif Almost then
			self.AlmostKillable = Almost
		end
	end

	for _, Minion in pairs(self.EnemyMinions.objects) do
		local Killable, Almost, TotalDamage = self:DoMinionCalc(Minion, Freeze)
		if Killable then
			self.SelectedMinion[#self.SelectedMinion + 1] = {minion = Minion, IncomingDamage = Minion.health / TotalDamage}
		elseif Almost then
			self.AlmostKillable = Almost
		end
	end

	if #self.SelectedMinion > 0 then
		table.sort(self.SelectedMinion, function(a, b) return a.IncomingDamage < b.IncomingDamage end)
		self.KillableMinion = self.SelectedMinion[1].minion
	end
end

function _Minions:UpdateMinionTables()
	local _EnemyMinions = {}
	local _AllyMinions = {}
	_EnemyMinions.objects = {}
	_AllyMinions.objects = {}

	for i, Minion in pairs(self.AllMinions.objects) do
		if Minion.team == TEAM_ENEMY and Data:IsMinion(Minion) then
			table.insert(_EnemyMinions.objects, Minion)
		end
	end

	for i, Minion in pairs(self.AllMinions.objects) do
		if Minion.team == TEAM_ALLY and Data:IsMinion(Minion) then
			table.insert(_AllyMinions.objects, Minion)
		end
	end


	self.EnemyMinions = _EnemyMinions
	self.AllyMinions = _AllyMinions
end

function _Minions:DoMinionCalc(Minion, Freeze, CustomDamage, CustomRange)
	if Orbwalker:CanOrbwalkTarget(Minion) or (CustomRange and ValidTarget(Minion, CustomRange)) then
		local PredictedDamage = 0
		local TotalDamage = 1
		local Killable, Almost = nil, nil

		for _, Attack in pairs(self.IncomingAttacks) do
			if Attack.Target == Minion and Attack.Particle then
				if MyHero:GetTimeToHitTarget(Minion) > Attack.LandsAt() then
					PredictedDamage = PredictedDamage + Attack.Damage
				end
				TotalDamage = TotalDamage + Attack.Damage
			end
		end

		local MyDamage

		if Freeze then
			MyDamage = MyHero:GetTotalAttackDamageAgainstTarget(Minion)
			MyDamage = MyDamage < 60 and MyDamage or 60
		else
			MyDamage = MyHero:GetTotalAttackDamageAgainstTarget(Minion)
			MyDamage = MyDamage * self:GetDamageScaling(Minion)
		end

		MyUnmodifiedDamage = MyDamage
		MyDamage = MyDamage * ((100 + (FarmMenu.FarmOffset * -1)) / 100)

		if CustomDamage then
			MyDamage = CustomDamage
		end


		if PredictedDamage < Minion.health and PredictedDamage + MyDamage - 2 > Minion.health then
			Killable = Minion
		elseif not Freeze then
			for _, func in pairs(Plugins.RegisteredBonusLastHitDamage) do
				if PredictedDamage < Minion.health and PredictedDamage + MyDamage + func(Minion, MyDamage) - 2 > Minion.health then
					Killable = Minion
				end
			end
		end

		if TotalDamage * self:GetWaitScale(Minion) + MyDamage - 5 > Minion.health then
			Almost = Minion
		end

		return Killable, Almost, TotalDamage
	end
end

function _Minions:GetPredictedDamageOnTarget(Target, LandsAt)
	local PredictedDamage = 0
	local TotalDamage = 0
	if Orbwalker:CanOrbwalkTarget(Minion) then
		for _, Attack in pairs(self.IncomingAttacks) do
			if Attack.Target == Minion and Attack.Particle then
				if MyHero:GetTimeToHitTarget(Minion) > LandsAt then
					PredictedDamage = PredictedDamage + Attack.Damage
				end
				TotalDamage = TotalDamage + Attack.Damage
			end
		end
	end

	return PredictedDamage, TotalDamage
end

function _Minions:_OnProcessSpell(Unit, Spell)
	if Data.UnitInfo[Unit.charName] and not Unit.isMe then
		for _, Minion in pairs(self.EnemyMinions.objects) do
			if ValidTarget(Minion) and Spell.target and Spell.target == Minion then
				self.IncomingAttacks[Unit.name] = _Attack(Unit, Spell)
				self.MinionTargets[Unit.networkID] = {Source = Unit, Target = Spell.target, startPos = {x = Unit.x, z = Unit.z}}
			end
		end
	end
end

function _Minions:_OnCreateObj(Object)
	if Object.name == "Mfx_bcm_mis.troy" or Object.name == "Mfx_pcm_mis.troy" or Object.name == "OrderTurretFire2_mis.troy" or Object.name == "ChaosTurretFire2_mis.troy" or Object.name == "TristannaBasicAttack_mis.troy" or Object.name == "SpikeBullet.troy" then
		local minDist = math.huge
		local sourceMinionName = nil

		for SourceName, Attack in pairs(self.IncomingAttacks) do
			if Attack.Speed > 0 and Attack.ProjectileName == Object.name then
				local tPos = Attack.StartPos

				if Attack.DistanceOffset then
					local tDir = (Attack.EndPos - Attack.StartPos):normalized()
					tPos = Attack.StartPos + tDir * 80
				end

				local tempDistance = Helper:GetDistance(tPos, Object)

				if tempDistance < minDist and tempDistance < 100 then
					minDist = tempDistance
					sourceMinionName = SourceName
				end
			end
		end

		if sourceMinionName and self.IncomingAttacks[sourceMinionName] then
			self.IncomingAttacks[sourceMinionName].Particle = Object
		end
	end
end

function _Minions:_OnDeleteObj(Obj)
	if self.KillableMinion and Obj == self.KillableMinion and not Orbwalker:CanMove() and Orb == Orbwalker.LastEnemyAttacked then
		myHero:HoldPosition()
	end
end

function _Minions:_OnRecvPacket(Packet)
	if Packet.header == 0xC3 then
		self.LastUpdate = GetTickCount()
		if #updates > 200 then
			table.remove(updates,1)
		end
		table.insert(updates, GetTickCount())
	end

	self.AverageUpdate = self:GetAverageUpdateTick()
	self.NextUpdate = GetTickCount() + self.AverageUpdate
end

function _Minions:GetAverageUpdateTick()
	local _Total = 0
	if #updates < 2 then return 128 end

	for i = #updates, 1, -1 do
		if updates[i-1] then
			_Total = _Total + (updates[i] - updates[i-1])
		end
	end
	return _Total / #updates
	--return 128
end

function _Minions:GetWaitScale(Minion)
	local _Count = self:GetNumberOfAttackingMinions(Minion)

	if FarmMenu.LaneClearMultiplier == 0 then
		return 2
	else
		local _Base = 2
		local _Modifier = FarmMenu.LaneClearMultiplier
		local _Scale = (_Modifier * 2) / 1000

		if _Count == 1 then
			return 1.5
		else
			local _Return = 2 - (_Count * _Scale)
			return (_Return < 1 and _Return or 1)
		end
	end
	
end

function _Minions:GetDamageScaling(Minion)
	local Base = 1
	if Data:IsCannonMinion(Minion) then
		Base = 0.8
	else
		Base = 0.85
	end

	local Scale = 0.015 * self:GetNumberOfAttackingMinions(Minion)

	return Base + Scale

	--return 0.7 + (Scale * AttackCount)
end

function _Minions:GetAttackDamageUpdate(Attack)
	if Attack.LandsAt < self.NextUpdate then
		return self.NextUpdate
	else
		for i = 1, 1000 do
			if Attack.LandsAt < self.NextUpdate + (self.AverageUpdate * i) then
				return self.NextUpdate + (self.AverageUpdate * i)
			end
		end
	end
	return self.NextUpdate
end

function _Minions:GetTowerMinion()
	for _, Attack in pairs(self.TowerAttacks) do
		local Minion = Attack.Target
		local Health = Minion.health
		local Damage = Attack.Damage

		if ValidTarget(Minion) then
			local MyDamage = MyHero:GetTotalAttackDamageAgainstTarget(Minion, true) * self:GetDamageScaling(Minion)

			local damage, total = self:DoMinionCalc(Minion, nil, true)
			if damage and type(damage) == "number" then
				Health = Health - damage -- remove minion damage
			end

			--local TimeToHit = MyHero:GetTimeToHitTarget(Attack.Target)

			--1 tower hit is enough for kill
			if Health - Damage > 0 and Health - Damage < MyDamage then
				return
			end

			--1 tower hit and 1 AA is enough for kill
			if Health - Damage > 0 and Health - Damage < MyDamage * 2 then
				return Minion
			end


			--2 tower hits is enough for kill
			if Health - (Damage * 2) > 0 and Health - (Damage * 2) < MyDamage then
				return
			end	

			if Health - (Damage * 2) - MyDamage * 2 > 0 then
				--return Minion
			end

		end

	end
end

function _Minions:LaneClear()
	if self.LastHitEnabled then
		if ConfigurationMenu.SupportMode then
			Orbwalker:Orbwalk(self:FindExecutableMinion())
		else
			if Orbwalker:CanShoot() then
				if Orbwalker:CanOrbwalkTarget(self.KillableMinion) then
					Orbwalker:Orbwalk(self.KillableMinion)
				elseif Orbwalker:CanOrbwalkTarget(self.AlmostKillable) then
					MyHero:Move()
				elseif FarmMenu.KillWards and Wards:GetAttackableWard() then
					Orbwalker:OrbwalkIgnoreChecks(Wards:GetAttackableWard())
				elseif Structures:CanOrbwalkStructure() then
					Orbwalker:OrbwalkIgnoreChecks(Structures:GetTargetStructure())
				else
					Orbwalker:Orbwalk(self:GetSecondLowestHealthMinion())
				end
			elseif self.KillableMinion and self.KillableMinion ~= Orbwalker.LastEnemyAttacked then
				self:LastHitWithSkill(self.KillableMinion)
				Orbwalker:Orbwalk(self.KillableMinion)
			elseif self.AlmostKillable and self.AlmostKillable ~= Orbwalker.LastEnemyAttacked then
				self:LastHitWithSkill(self.AlmostKillable)
				Orbwalker:Orbwalk(self.AlmostKillable)
			else
				MyHero:Move()
			end
		end
	else
		MyHero:Move()
	end
end

function _Minions:LastHit()
	if self.LastHitEnabled then
		if ConfigurationMenu.SupportMode then
			Orbwalker:Orbwalk(self:FindExecutableMinion())
		else
			if Orbwalker:CanShoot() then
				if Orbwalker:CanOrbwalkTarget(self.KillableMinion) then
					Orbwalker:Orbwalk(self.KillableMinion)
				else
					Orbwalker:Orbwalk(self.TowerMinion)
				end
			elseif self.KillableMinion and self.KillableMinion ~= Orbwalker.LastEnemyAttacked then
				self:LastHitWithSkill(self.KillableMinion)
				Orbwalker:Orbwalk(self.KillableMinion)
			elseif self.AlmostKillable and self.AlmostKillable ~= Orbwalker.LastEnemyAttacked then
				self:LastHitWithSkill(self.AlmostKillable)
				Orbwalker:Orbwalk(self.AlmostKillable)
			else
				MyHero:Move()
			end
		end
	else
		MyHero:Move()
	end
end

function _Minions:GetNumberOfAttackingMinions(Target)
	local Count = 0
	for _, Minion in pairs(self.MinionTargets) do
		if Minion.Target == Target then
			Count = Count + 1
		end
	end
	-- for _, Attack in pairs(self.IncomingAttacks) do
	-- 	if Attack.Target.networkID == Target.networkID and not Attack.IsAllyAttack then
	-- 		Count = Count + 1
	-- 	end
	-- end
	return Count
end

function _Minions:GetNumberOfAttackingAllies(Target)
	local Count = 0
	for _, Attack in pairs(self.IncomingDamage) do
		if Attack.Target.networkID == Target.networkID and Attack.IsAllyAttack then
			Count = Count + 1
		end
	end
	return Count
end

function _Minions:AttackIsValid(Attack)
	if not ValidTarget(Attack.Source, 2000, false) then
		if Attack.Type == ATTACK_TYPE_MELEE_MINION then
			return false
		elseif (Attack.Type == ATTACK_TYPE_CASTER_MINION or Attack.Type == ATTACK_TYPE_CANNON_MINION) and GetTickCount() < Attack.FiresAt then
			return false
		end
	end
	
	if not ValidTarget(Attack.Target) or GetTickCount() > Attack.UpdatesAt then
		return false
	end
	
	if Helper:GetDistance(Attack.Source, Attack.Origin) > 3 then
		if GetTickCount() < Attack.FiresAt then
			return false
		end
	end

	if Attack.IsAllyAttack and GetTickCount() > Attack.LandsAt then
		return false
	end
					
	return true			
end

function _Minions:GetPredictedDamage(Attack)
	if not self:AttackIsValid(Attack) and not Attack.IsAllyAttack then
		return 0, 0
	else
		local TimeToHit = 0
		if MyHero.IsMelee then
			TimeToHit = Orbwalker.LastWindUp + Orbwalker:GetNextAttackTime()
		else
			TimeToHit = MyHero:GetTimeToHitTarget(Attack.Target)
		end
		
		if (not Attack.IsAllyAttack and TimeToHit > Attack.UpdatesAt) or (Attack.IsAllyAttack and TimeToHit > Orbwalker:GetAllyTimeToAttack(Attack.Source, Attack.Target))  then
			return Attack.Damage, Attack.Damage
		else
			return 0, Attack.Damage
		end
	end
end

function _Minions:GetKillableMinion(Freeze)
	for _, Cannon in pairs(self:GetEnemyCannons()) do
		local damage, total = self:DoMinionCalc(Cannon, Freeze)
		if damage or total then
			return damage, total
		end
	end

	for _, EnemyMinion in ipairs(self.EnemyMinions.objects) do
		local damage, total = self:DoMinionCalc(EnemyMinion, Freeze)
		if damage or total then
			return damage, total
		end
	end
end

-- function _Minions:DoMinionCalc(EnemyMinion, Freeze, IsTower)
-- 	local Damage, TotalDamage, MyDamage = 0, 0, 0

-- 	if Freeze then
-- 		MyDamage = MyHero:GetTotalAttackDamageAgainstTarget(EnemyMinion, true)
-- 		MyDamage = MyDamage < 50 and MyDamage or 50
-- 	else
-- 		MyDamage = MyHero:GetTotalAttackDamageAgainstTarget(EnemyMinion, true)
-- 	end

-- 	if IsTower then
-- 		MyDamage = MyDamage
-- 	end

-- 	if EnemyMinion.charName:find("MechCannon") and not Freeze then
-- 		MyDamage = MyDamage * 0.8
-- 	end

-- 	if Orbwalker:CanOrbwalkTarget(EnemyMinion) or (MyHero.IsMelee and Helper:GetDistance(EnemyMinion) < MyHero.TrueRange + 75) then
-- 		if EnemyMinion.health < MyDamage * ((100 + (FarmMenu.FarmOffset * -1)) / 100) or self:CanExecuteMinion(EnemyMinion) then
-- 			return EnemyMinion, nil
-- 		elseif self:GetNumberOfAttackingMinions(EnemyMinion) <= 3 and self:GetNumberOfAttackingAllies(EnemyMinion) < 1 and not IsTower then
-- 			return
-- 		else
-- 			MyDamage = MyDamage * self:GetDamageScaling(EnemyMinion)
-- 			MyDamage = MyDamage * ((100 + (FarmMenu.FarmOffset * -1)) / 100)
-- 			for _, Attack in pairs(self.IncomingDamage) do
-- 				if Attack.Target.networkID == EnemyMinion.networkID then
-- 					local _Damage, _TotalDamage = self:GetPredictedDamage(Attack)
-- 					Damage = Damage + _Damage
-- 					TotalDamage = TotalDamage + _TotalDamage
-- 				end
-- 			end

-- 			if EnemyMinion.health - Damage < MyDamage then
-- 				return EnemyMinion, nil
-- 			end
-- 			if not Freeze then
-- 				for _, func in pairs(Plugins.RegisteredBonusLastHitDamage) do
-- 					if EnemyMinion.health - Damage < MyDamage + func(EnemyMinion, MyDamage) then
-- 						return EnemyMinion, nil
-- 					end
-- 				end
-- 			end
-- 			if EnemyMinion.health - (TotalDamage * (1 + (FarmMenu.LaneClearMultiplier / 100))) < MyDamage then
-- 				return nil, EnemyMinion
-- 			end
-- 		end
-- 	end
-- end

function _Minions:FindExecutableMinion(Minion)
	if myHero.range < 300 then
		for _, Minion in pairs(self.EnemyMinions.objects) do
			if Orbwalker:CanOrbwalkTarget(Minion) and self:CanExecuteMinion(Minion) then
				return Minion
			end
		end
	end
end

function _Minions:CanExecuteMinion(Minion)
	return MyHero.HasSpoils and Minion.health < 200 + myHero.damage and Helper:CountAlliesInRange(1000) > 0 
end

function _Minions:GetKillableMinionFreeze()
	return self:GetKillableMinion(true)
end

function _Minions:GetLowestHealthMinion()
	for i =1, #self.EnemyMinions.objects, 1 do
		local Minion = self.EnemyMinions.objects[i]
		if Orbwalker:CanOrbwalkTarget(Minion) then
			return Minion
		end
	end
end

function _Minions:GetSecondLowestHealthMinion()
	local found = nil
	for i =1, #self.EnemyMinions.objects, 1 do
		local Minion = self.EnemyMinions.objects[i]
		if Orbwalker:CanOrbwalkTarget(Minion) and found then
			return Minion
		elseif Orbwalker:CanOrbwalkTarget(Minion) then
			found = Minion
		end
	end
	return found
end

function _Minions:GetEnemyCannons()
	local Cannons = {}
	for _, Minion in pairs(self.EnemyMinions.objects) do
		if Data:IsCannonMinion(Minion) then
			table.insert(Cannons, Minion)
		end
	end
	return Cannons
end

function _Minions:SetMixedModeLastHitPriority(Enabled)
	MixedModeMenu.MinionPriority = Enabled
end

function _Minions:SetLaneClearLastHitPriority(Enabled)
	LaneClearMenu.MinionPriority = Enabled
end

function _Minions:ToggleLastHit(Enabled)
	self.LastHitEnabled = Enabled
end

function _Minions:UpdateAttackIntervals()
	for _, Attack in pairs(self.IncomingDamage) do
		Attack.UpdatesAt = VIP_USER and self:GetAttackDamageUpdate(self) or Attack.LandsAt
	end
end

function _Minions:LastHitWithSkill(Minion)
	if self:GetNumberOfAttackingMinions(Minion) < 1 then
		return
	end
	for _, Skill in pairs(Skills:GetLastHitSkills()) do
		local dmgString = "";
		if Skill.Key == _Q then
			dmgString = "Q"
		elseif Skill.Key == _W then
			dmgString = "W"
		elseif Skill.Key == _E then
			dmgString = "E"
		elseif Skill.Key == _R then
			dmgString = "R"
		end
		if FarmMenu["FarmSkill"..Skill.RawName] then
			if ValidTarget(Minion, Skill.Range) then
				if Skill.Type == SPELL_TARGETED then
					local _Damage = getDmg(dmgString, Minion, myHero)
					if _Damage > Minion.health then
						CastSpell(Skill.Key, Minion)
					end
				elseif Skill.Type == SPELL_SELF_AT_MOUSE then
					CastSpell(Skill.Key, mousePos.x, mousePos.z)
				else
					CastSpell(Skill.Key)
				end
			end
		end	
	end
end

--[[
		_Attack Class
]]

ATTACK_TYPE_MELEE_MINON = 0
ATTACK_TYPE_CASTER_MINION = 1
ATTACK_TYPE_CANNON_MINION = 2
ATTACK_TYPE_PLAYER = 3
ATTACK_TYPE_TOWER = 4


class '_Attack'

function _Attack:__init(Source, Spell)
	self.Source = Source
	self.Target = Spell.target
	self.StartTime = GetTickCount()
	self.WindUp = (Spell.windUpTime or 0) * 1000
	self.Speed = self:GetInfo().projSpeed or 0
	self.Damage = self:CalcDamageOfAttack(self.Source, self.Target)
	self.StartPos = Vector(Source.x, Source.y + (self:GetInfo().yOffset or 0), Source.z)
	self.EndPos = Vector(Spell.target.x, Spell.target.y, Spell.target.z)
	self.Spell = Spell
	self.DelayOffset = self:GetInfo().delayOffset or 0
	self.DistanceOffset = self:GetInfo().distOffset or 0
	self.Particle = nil
	self.ProjectileName = self:GetInfo().projectileName
	self.IsAllyAttack = Source.type == myHero.type

	self.LandsAt = function()
		if self.Speed == 0 then
			return self.Started + self.WindUp + self.DelayOffset
		elseif self.Speed > 0 and self.Particle then
			return GetTickCount() + Helper:GetDistance(self.Particle, self.Target) / self.Speed + self.DelayOffset
		else
			return GetTickCount() + 2000
		end
	end
end


function _Attack:GetType(Source)
	if Source.charName:find("Wizard") then
		return ATTACK_TYPE_CASTER_MINION
	elseif Source.charName:find("Basic") then
		return ATTACK_TYPE_MELEE_MINON
	elseif Source.charName:find("MechCannon") then
		return ATTACK_TYPE_CANNON_MINION
	elseif Source.type == myHero.type then
		return ATTACK_TYPE_PLAYER
	elseif Source.type == "obj_AI_Turret" then
		return ATTACK_TYPE_TOWER
	else
		return -1
	end
end

function _Attack:CalcDamageOfAttack(source, target)
	local armorPen = 0
	local armorPenPercent = 0
	
	local magicPen = 0
	local magicPenPercent = 0
	
	local magicDamage = 0
	local physDamage = source.totalDamage
	
	local dmgReductionPercent = 0

	local totalDamage = physDamage

	local Info = self:GetInfo()

	if Info.isTurret then
		armorPenPercent = .3
	end

	-- if Info.isMinion then
	-- 	armorPenPercent = 0
	-- 	physDamage = physDamage * 1.3
	-- end

	-- if Info.isSiegeMinion then
	-- 	dmgReductionPercent = .3
	-- end

	return (physDamage * (100/(100 + target.armor * (1-armorPenPercent)))  
	 + magicDamage * (100/(100 + target.magicArmor * (1-magicPenPercent))) ) * (1-dmgReductionPercent)
end

function _Attack:GetInfo()
	return Data.UnitInfo[self.Source.charName]
end

function _Attack:StillViable()
	if not self.Source or not self.Target or self.Source.dead or self.Target.dead then
		return false
	end

	local TimeElapsed = GetTickCount() - self.StartTime

	if self.Speed > 0 and TimeElapsed > self.WindUp + 100 and not self.Particle then
		return false
	elseif self.Speed == 0 and TimeElapsed > self.WindUp + self.DelayOffset then
		return false
	end

	return true
end

--[[ 
	_ChampionBuffs Class
]]

class '_ChampionBuffs' ChampionBuffs = nil

function _ChampionBuffs:__init()
	AddCreateObjCallback(function(Obj) self:_OnCreateObj(Obj) end)
	AddDeleteObjCallback(function(Obj) self:_OnDeleteObj(Obj) end)

	self.HasPassive = false
	self.RangerStacks = 0
	self.qBuff = 0

	ChampionBuffs = self
end

function _ChampionBuffs:_OnCreateObj(object)
	if myHero.dead then return end
    if object.name:lower():find("caitlyn_headshot_rdy") and Helper:GetDistance(object) < 65 then self.caitlynPassive = true end
    if object.name:lower():find("Lucian_P_buf.troy") and Helper:GetDistance(object) < 65 then self.LucianPassive = true end
    if object.name == "RengarPassiveMax.troy" and Helper:GetDistance(object) < 65 then self.rengarStacks = 5 end
    if object.name == "RighteousFuryHalo_buf.troy" and Helper:GetDistance(object) < 65 then self.kayleBuff = true end
    if object.name == "Draven_Q_buf.troy" and Helper:GetDistance(object) < 65 then self.qBuff = (self.qBuff >= 0 and self.qBuff + 1) or 0 end
    if object.name == "Jayce_Hex_Buff_Ready.troy" and Helper:GetDistance(object) < 65 then self.jayceWcasted = true end
end

function _ChampionBuffs:_OnDeleteObj(object)
    if object.name:lower():find("caitlyn_headshot_rdy") and Helper:GetDistance(object) < 65 then self.caitlynPassive = false end
    if object.name:lower():find("Lucian_P_buf.troy") and Helper:GetDistance(object) < 65 then self.LucianPassive = true end
    if object.name == 'Lucian_R_self.troy' and LucianCulling == true and Helper:GetDistance(object) < 65 then self.LucianCulling = false end
    if object.name == "RengarPassiveMax.troy" and Helper:GetDistance(object) < 65 then self.rengarStacks = 0 end
    if object.name == "RighteousFuryHalo_buf.troy" and Helper:GetDistance(object) < 65 then self.kayleBuff = false end
    if object.name == "Draven_Q_buf.troy" and Helper:GetDistance(object) < 600 then self.qBuff = self.qBuff - 1 end
    if object.name == "Jayce_Hex_Buff_Ready.troy" and Helper:GetDistance(object) < 65 then self.jayceWcasted = false end
end

function _ChampionBuffs:GetBonusDamage()
    local additionalDamage = {
        Teemo       = myHero:GetSpellData(_E).level > 0 and ((GetSpellData(_E).level * 10) + (myHero.ap * 0.3)) or 0,
        Vayne       = myHero:GetSpellData(_Q).level > 0 and myHero:CanUseSpell(_Q) == SUPRESSED and (((0.05*myHero:GetSpellData(_Q).level) + 0.25 )*(myHero.totalDamage)) or 0,
        Corki       = myHero.totalDamage/10,
        Caitlyn     = self.caitlynPassive and myHero.totalDamage * 1.5 or 0,
        Rengar      = myHero:GetSpellData(_Q).level > 0 and myHero:CanUseSpell(_Q) == SUPRESSED and (self.rengarStacks == 5 and (30*myHero:GetSpellData(_Q).level)+myHero.totalDamage or (30*myHero:GetSpellData(_Q).level)) or 0 ,
        MissFortune = myHero:GetSpellData(_W).level > 0 and ((2+2*myHero:GetSpellData(_W).level) + (myHero.ap*0.05)) or 0,
        Sivir       = myHero:GetSpellData(_W).level > 0 and myHero:CanUseSpell(_W) == SUPRESSED and (5+(15*myHero:GetSpellData(_W).level)) or 0,
        Orianna     = (8*(((myHero.level - 1) / 3) + 1) + 2 + 0.15*myHero.ap),
        Draven      = myHero:GetSpellData(_Q).level > 0 and self.qBuff > 0 and (myHero.totalDamage*(0.35 + (0.1 * myHero:GetSpellData(_Q).level))) or 0,
        Kayle       = self.kayleBuff and (10 + (10*myHero:GetSpellData(_E).level) + (0.4*myHero.ap)) or 0,
        Jayce       = self.jayceWcasted and ((myHero.totalDamage*(15*myHero:GetSpellData(_W).level+55)/100) - myHero.totalDamage) or 0,
        Lucian      = self.LucianPassive and myHero.totalDamage or 0
    }
    return additionalDamage[myHero.charName] or 0
end

--[[
		_Jungle Class
]]

class '_Jungle' Jungle = nil

function _Jungle:__init()
	self.JungleMonsters = {}
	Jungle = self
	for i = 0, objManager.maxObjects do
	local object = objManager:getObject(i)
		if Data:IsJungleMinion(object) then
			table.insert(self.JungleMonsters, object)
		end
	end

	AddCreateObjCallback(function(Object) self:_OnCreateObj(Object) end)
	AddDeleteObjCallback(function(Object) self:_OnDeleteObj(Object) end)
end

function _Jungle:_OnCreateObj(Object)
	if Data:IsJungleMinion(Object) then
		table.insert(self.JungleMonsters, Object)
	end
end

function _Jungle:_OnDeleteObj(Object)
	if Data:IsJungleMinion(Object) then
		for i, Obj in pairs(self.JungleMonsters) do
			if obj == Object then
				table.remove(self.JungleMonsters, i)
			end
		end
	end
end

function _Jungle:GetJungleMonsters()
	return self.JungleMonsters
end

function _Jungle:GetAttackableMonster()
	local HighestPriorityMonster =  nil
	local Priority = 0
	for _, Monster in pairs(self.JungleMonsters) do
		if Orbwalker:CanOrbwalkTarget(Monster) then
			local CurrentPriority = Data:GetJunglePriority(Monster.name)
			if Monster.health < MyHero:GetTotalAttackDamageAgainstTarget(Monster) then
				return Monster
			elseif not HighestPriorityMonster then
				HighestPriorityMonster = Monster
				Priority = CurrentPriority
			else
				if CurrentPriority < Priority then
					HighestPriorityMonster = Monster
					Priority = CurrentPriority
				end
			end
		end
	end
	return HighestPriorityMonster
end

function _Jungle:GetFocusedMonster()
	if GetTarget() and Data:IsJungleMinion(GetTarget()) then
		return GetTarget()
	end
end

class '_Structures' Structures = nil

function _Structures:__init()
	Structures = self
	self.TowerCollisionRange = 88.4
	self.InhibCollisionRange = 205
	self.NexusCollisionRange = 300
	self.TowerRange = 950
	self.EnemyTowers = {}
	self.AllyTowers = {}

	for i = 1, objManager.maxObjects do
		local Object = objManager:getObject(i)
		if Object and Object.type == "obj_AI_Turret" then
			if Object.team == myHero.team then
				table.insert(self.AllyTowers, Object)
			else
				table.insert(self.EnemyTowers, Object)
			end
		end
	end

	AddDeleteObjCallback(function(obj) self:_OnDeleteObj(obj) end)
end

function _Structures:_OnDeleteObj(Object)
	for i, Tower in pairs(self.AllyTowers) do
		if Object == Tower then
			table.remove(self.AllyTowers, i)
			return
		end
	end
	for i, Tower in pairs(self.EnemyTowers) do
		if Object == Tower then
			table.remove(self.EnemyTowers, i)
			return
		end
	end
end

function _Structures:TowerTargetted()
	return GetTarget() and GetTarget().type == "obj_AI_Turret" and GetTarget().team ~= myHero.team
end

function _Structures:InhibTargetted()
	return GetTarget() and GetTarget().type == "obj_BarracksDampener" and GetTarget().team ~= myHero.team
end

function _Structures:NexusTargetted()
	return GetTarget() and GetTarget().type == "obj_HQ" and GetTarget().team ~= myHero.team
end

function _Structures:CanOrbwalkStructure()
	return self:CanOrbwalkTower() or self:CanOrbwalkInhib() or self:CanOrbwalkNexus()
end

function _Structures:GetTargetStructure()
	return GetTarget()
end

function _Structures:CanOrbwalkTower()
	return self:TowerTargetted() and Helper:GetDistance(GetTarget()) - self.TowerCollisionRange < MyHero.TrueRange
end

function _Structures:CanOrbwalkInhib()
	return self:InhibTargetted() and Helper:GetDistance(GetTarget()) - self.InhibCollisionRange < MyHero.TrueRange
end

function _Structures:CanOrbwalkNexus()
	return self:NexusTargetted() and Helper:GetDistance(GetTarget()) - self.NexusCollisionRange < MyHero.TrueRange
end

function _Structures:PositionInEnemyTowerRange(Pos)
	for _, Tower in pairs(self.EnemyTowers) do
		if Helper:GetDistance(Tower, Pos) <= self.TowerRange then
			return true
		end
	end
	return false
end

function _Structures:PositionInAllyTowerRange(Pos)
	for _, Tower in pairs(self.AllyTowers) do
		if Helper:GetDistance(Tower, Pos) <= self.TowerRange then
			return true
		end
	end
	return false
end

function _Structures:GetClosestEnemyTower(Pos)
	local ClosestTower, Distance = nil, 0
	for i, Tower in pairs(self.EnemyTowers) do
		if not Tower or not Pos then return end
		if not ClosestTower then
			ClosestTower, Distance = Tower, Helper:GetDistance(Pos, Tower)
		elseif Helper:GetDistance(Pos, Tower) < Distance then
			ClosestTower, Distance = Tower, Helper:GetDistance(Pos, Tower)
		end
	end
	return ClosestTower
end

function _Structures:GetClosestAllyTower(Pos)
	local ClosestTower, Distance = nil, 0
	for _, Tower in pairs(self.AllyTowers) do
		if not ClosestTower then
			ClosestTower, Distance = Tower, Helper:GetDistance(Pos, Tower)
		elseif Helper:GetDistance(Pos, Tower) < Distance then
			ClosestTower, Distance = Tower, Helper:GetDistance(Pos, Tower)
		end
	end
	return ClosestTower
end

--[[
		_Skills Class
]]

class '_Skills' Skills = nil

function _Skills:__init()
 	self.SkillsList = {}
 	Skills = self
 	if VIP_USER then require "Collision" end
 	AddTickCallback(function() self:_OnTick() end)
end

function _Skills:_OnTick()
	for _, Skill in pairs(self.SkillsList) do
		if Keys.AutoCarry and AutoCarryMenu[Skill.RawName.."AutoCarry"] or
			Keys.MixedMode and MixedModeMenu[Skill.RawName.."MixedMode"] or
			Keys.LaneClear and LaneClearMenu[Skill.RawName.."LaneClear"] then
			Skill.Active = true
		else
			Skill.Active = false
		end
	end
	self:SortSkillOrder()
end

function _Skills:CastAll(Target)
	for _, Skill in ipairs(self.SkillsList) do
		if Skill.Enabled then
			Skill:Cast(Target)
		end
	end
end

function _Skills:GetSkill(Key)
	for _, Skill in pairs(self.SkillsList) do
		if Skill.Key == Key then
			return Skill
		end
	end
end

function _Skills:GetSkillMenu(Key)
	if MenuManager.SkillMenu[Key] then
		return MenuManager.SkillMenu[Key]
	else
		return {CastPrio = 0}
	end
end

function _Skills:SortSkillOrder()
	table.sort(self.SkillsList, function(a, b) return self:GetSkillMenu(a.Key).CastPrio < self:GetSkillMenu(b.Key).CastPrio end)
end

function _Skills:HasSkillReady()
	for _, Skill in pairs(self.SkillsList) do
		if Skill.Ready then
			return true
		end
	end
end

function _Skills:NewSkill(enabled, key, range, displayName, type, minMana, afterAttack, reqAttackTarget, speed, delay, width, collision, isReset)
	return _Skill(enabled, key, range, displayName, type, minMana, afterAttack, reqAttackTarget, speed, delay, width, collision, isReset, true)
end

function _Skills:DisableAll()
	for _, Skill in pairs(self.SkillsList) do
		Skill.Enabled = false
	end
end

function _Skills:GetLastHitSkills()
	local Skills = {}
	for _, Skill in pairs(self.SkillsList) do
		if Skill.Type == SPELL_TARGETED or Skill.IsReset then
			table.insert(Skills, Skill)
		end
	end
	return Skills
end

--[[
		_Skill Class
]]

class '_Skill'

SPELL_TARGETED = 1
SPELL_LINEAR = 2
SPELL_CIRCLE = 3
SPELL_CONE = 4
SPELL_LINEAR_COL = 5
SPELL_SELF = 6
SPELL_SELF_AT_MOUSE = 7
AutoCarry.SPELL_TARGETED = 1
AutoCarry.SPELL_LINEAR = 2
AutoCarry.SPELL_CIRCLE = 3
AutoCarry.SPELL_CONE = 4
AutoCarry.SPELL_LINEAR_COL = 5
AutoCarry.SPELL_SELF = 6
AutoCarry.SPELL_SELF_AT_MOUSE = 7

-- --[[
-- 		Initialise _Skill class

-- 		enabled  			Boolean - set true for auto carry to automatically cast it, false for manual control in plugin
-- 		key 				Spell key, e.g _Q
-- 		range 				Spell range
-- 		displayName 		The name to display in menus
-- 		type 				SPELL_TARGETED, SPELL_LINEAR, SPELL_CIRCLE, SPELL_CONE, SPELL_LINEAR_COL, SPELL_SELF, SPELL_SELF_AT_MOUSE
-- 		minMana 			Minimum percentage mana before cast is allowed
-- 		afterAttack 		Boolean - set true to only cast right after an auto attack
-- 		reqAttackTarget 	Boolean - set true to only cast if a target is in attack range
-- 		speed 				Speed of the projectile for skillshots
-- 		delay 				Delay of the spell for skillshots
-- 		width 				Width of the projectile for skillshots
-- 		collision 			Boolean - set true to check minion collision before casting

-- ]]
function _Skill:__init(enabled, key, range, displayName, type, minMana, afterAttack, reqAttackTarget, speed, delay, width, collision, isReset, custom)
	self.Key = key
	self.Range = range
	self.DisplayName = displayName
	self.RawName = self.DisplayName:gsub("[^A-Za-z0-9]", "")
	self.Type = type
	self.MinMana = minMana or 0
	self.AfterAttack = afterAttack or false
	self.ReqAttackTarget = reqAttackTarget or false
	self.Speed = speed or 0
	self.Delay = delay or 0
	self.Width = width or 0
	self.Collision = collision
	self.IsReset = isReset or false
	self.IsCustom = custom
	self.Active = true
	self.Enabled = enabled or false
	self.Ready = false
	if VIP_USER then
		self.KloPredObject = ProdictManager.GetInstance()
		if self.Type ~= SPELL_CONE then
			self.KloPredCallback = function(unit, pos, castSpell) 
				if GetDistanceSqr(pos, castSpell.Source) < castSpell.RangeSqr and  myHero:CanUseSpell(castSpell.Name) == READY then
					if (self.Collision and not self:GetCollision(pos)) or not self.Collision then
			        	CastSpell(castSpell.Name, pos.x, pos.z)
			        end
			    end 
			end
		else
			self.KloPredCallback = function(unit, pos, castSpell) 
				local conePos = GetCone(self.Delay, self.Width)
				if conePos then
					CastSpell(castSpell.Name, conePos.x, conePos.z)
				end
			end
		end
		
		self.KloPred = self.KloPredObject:AddProdictionObject(self.Key, self.Range, self.Speed * 1000, self.Delay / 1000, self.Width)
	end

	AddTickCallback(function() self:_OnTick() end)

	table.insert(Skills.SkillsList, self)
end

function _Skill:_OnTick()
	if self.Enabled then
		if Skills:GetSkillMenu(self.Key).ManualCast and Crosshair:GetTarget() then
			self:ForceCast(Crosshair:GetTarget())
		end
	end
	self.Ready = myHero:CanUseSpell(self.Key) == READY
	if self.Enabled and not self.IsCustom then
		self.reqAttackTarget = Skills:GetSkillMenu(self.Key).RequiresTarget
	end
end

function _Skill:Cast(Target, ForceCast)
	if not ForceCast then
		if (not self.Active and self.Enabled) or (not self.Enabled and not self.IsCustom) then
			return
		elseif self.AfterAttack and not Orbwalker:IsAfterAttack() then
			return
		elseif not self:ValidSkillTarget(Target) then
			return
		elseif (self.ReqAttackTarget and not Orbwalker:CanOrbwalkTarget(Target)) then
			return
		end
	end
	if not self:IsReady() then 
		return 
	end


	if self.Type == SPELL_SELF then
		CastSpell(self.Key)
	elseif self.Type == SPELL_SELF_AT_MOUSE then
		CastSpell(self.Key, mousePos.x, mousePos.z)
	elseif self.Type == SPELL_TARGETED then
		if ValidTarget(Target, self.Range) then
			CastSpell(self.Key, Target)
		end
	elseif self.Type == SPELL_CONE then
		if ValidTarget(Target, self.Range) then
			if not VIP_USER then
				local predPos = self:GetPrediction(Target)
				if predPos then
					CastSpell(self.Key, predPos.x, predPos.z)
				end
			else
				self.KloPred:GetPredictionCallBack(Target, self.KloPredCallback)
			end
		end
	elseif self.Type == SPELL_LINEAR or self.Type == SPELL_CIRCLE then
		if ValidTarget(Target) then
			if not VIP_USER then
				local predPos = self:GetPrediction(Target)
				if predPos then
					CastSpell(self.Key, predPos.x, predPos.z)
				end
			else
				self.KloPred:GetPredictionCallBack(Target, self.KloPredCallback)
			end
		end
	elseif self.Type == SPELL_LINEAR_COL then
		if ValidTarget(Target) then		
			if self.Collision then 
				if not VIP_USER then
					local predPos = self:GetPrediction(Target)
					if predPos then
						local collision = self:GetCollision(predPos)
						if not collision or ForceCast then
							CastSpell(self.Key, predPos.x, predPos.z)
						end
					end
				else
					self.KloPred:GetPredictionCallBack(Target, self.KloPredCallback)
				end
			end
		end
	end
end

function _Skill:ForceCast(Target)
	self:Cast(Target, true)
end

function _Skill:GetPrediction(Target)
	if VIP_USER then
		pred = TargetPredictionVIP(self.Range, self.Speed*1000, self.Delay/1000, self.Width)
		if pred and pred:GetHitChance(Target) > ConfigMenu.HitChance/100 then
			return pred:GetPrediction(Target)
		end
	elseif not VIP_USER then
		pred = TargetPrediction(self.Range, self.Speed, self.Delay, self.Width)
		return pred:GetPrediction(Target)
	end
end

function _Skill:GetCollision(pos)
	if VIP_USER and self.Collision then
		local col = Collision(self.Range, self.Speed*1000, self.Delay/1000, self.Width)
		return col:GetMinionCollision(myHero, pos)
	elseif self.Collision then
		for _, Minion in pairs(Minions.EnemyMinions.objects) do
			if ValidTarget(Minion) and myHero.x ~= Minion.x then
				local myX = myHero.x
				local myZ = myHero.z
				local tarX = pos.x
				local tarZ = pos.z
				local deltaX = myX - tarX
				local deltaZ = myZ - tarZ
				local m = deltaZ/deltaX
				local c = myX - m*myX
				local minionX = Minion.x
				local minionZ = Minion.z
				local distanc = (math.abs(minionZ - m*minionX - c))/(math.sqrt(m*m+1))
				if distanc < self.Width and ((tarX - myX)*(tarX - myX) + (tarZ - myZ)*(tarZ - myZ)) > ((tarX - minionX)*(tarX - minionX) + (tarZ - minionZ)*(tarZ - minionZ)) then
					return true
				end
			end
	   end
	   return false
	end
end

function _Skill:GetHitChance(pred)
	if VIP_USER then
		return pred:GetHitChance(target) > ConfigMenu.HitChance/100 
	end
end

function _Skill:ValidSkillTarget(Target) 
	if not self.IsCustom then
		local _Menu = Skills:GetSkillMenu(self.Key)
		if not Target or myHero.mana / myHero.maxMana * 100 < _Menu.MinMana or Target.health / Target.maxHealth * 100 < _Menu.MinHealth or Target.health / Target.maxHealth * 100 > _Menu.MaxHealth then
			return false
		end
		return true
	end
	return true
end

function _Skill:GetRange()
	return self.reqAttackTarget and MyHero.TrueRange or self.Range
end

function _Skill:IsReady()
	return myHero:CanUseSpell(self.Key) == READY
end

--[[
		_Items Class
]]

class '_Items' Items = nil

function _Items:__init()
	self.ItemList = {}
	Items = self

	AddTickCallback(function() self:_OnTick() end)
end

function _Items:_OnTick()
	for _, Item in pairs(self.ItemList) do
		if Keys.AutoCarry and AutoCarryMenu[Item.RawName.."AutoCarry"] or
			Keys.MixedMode and MixedModeMenu[Item.RawName.."MixedMode"] or
			Keys.LaneClear and LaneClearMenu[Item.RawName.."LaneClear"] then
			Item.Active = true
		else
			Item.Active = false
		end
	end
end

function _Items:UseAll(Target)
	if Target and Target.type == myHero.type then
		for _, Item in pairs(self.ItemList) do
			Item:Use(Target)
		end
	end
end

function _Items:UseItem(ID, Target)
	for _, Item in pairs(self.ItemList) do
		if Item.ID == ID then
			Item:Use(Target)
		end
	end
end

function _Items:GetItem(ID)
	for _, Item in pairs(self.ItemList) do
		if Item.ID == ID then
			return Item
		end
	end
end

function _Items:GetBotrkBonusLastHitDamage(StartingDamage, Target)
	local _BonusDamage = 0
	if GetInventoryHaveItem(3153) then
		if ValidTarget(Target) then
			_BonusDamage = Target.health / 20
			if _BonusDamage >= 60 then
				_BonusDamage = 60
			end
		end
	end
	return _BonusDamage
end

--[[
		_Item Class
]]

class '_Item'

--TODO: Add Muramana
function _Item:__init(_Name, _ID, _RequiresTarget, _Range, _Override)
	self.Name = _Name
	self.RawName = self.Name:gsub("[^A-Za-z0-9]", "")
	self.ID = _ID
	self.RequiresTarget = _RequiresTarget
	self.Range = _Range
	self.Slot = nil
	self.Override = _Override
	self.Active = true
	self.Enabled = true

	table.insert(Items.ItemList, self)
end

function _Item:Use(Target)
	if self.Override then 
		return self.Override() 
	end
	if self.RequiresTarget and not Target then 
		return 
	end
	if not self.Active or not self.Enabled then
		return
	end

	self.Slot = GetInventorySlotItem(self.ID)

	if self.Slot then	
		if self.ID == 3153 then -- BRK
			local _Menu = MenuManager:GetActiveMenu()
			if _Menu and _Menu.botrkSave then
				if  myHero.health <= myHero.maxHealth * 0.65 then
					CastSpell(self.Slot, Target)
				end
			elseif _Menu and _Menu.Active then
				CastSpell(self.Slot, Target)
			end
		elseif self.ID == 3042 then -- Muramana
			if not MuramanaIsActive() then
				MuramanaOn()
			end
		elseif self.ID == 3069 then -- Talisman of Ascension
			if Helper:CountAlliesInRange(600) > 0 then
				CastSpell(self.Slot)
			end
		elseif not self.RequiresTarget and Orbwalker:CanOrbwalkTarget(Target) then
			CastSpell(self.Slot)
		elseif self.RequiresTarget and ValidTarget(Target) and Helper:GetDistance(Target) <= self.Range then
			CastSpell(self.Slot, Target)
		end
	end
end

--[[
		_Helper Class
]]

class '_Helper' Helper = nil

function _Helper:__init()
	self.Tick = 0
	self.Latency = 0
	self.Colour = {Green = 0x00FF00}
	self.EnemyTable = {}
	Helper = self
	self.EnemyTable = GetEnemyHeroes()
	self.AllyTable = GetAllyHeroes()
	self.AllHeroes = {}
	self:GetAllHeroes()
	self.DebugStrings = {}
	AddTickCallback(function() self:_OnTick() end)
	AddDrawCallback(function() self:_OnDraw() end)
end

function _Helper:_OnTick()
	self.Tick = GetTickCount()
	self.Latency = GetLatency()
end

function _Helper:GetDistance(p1, p2)
	p2 = p2 or myHero

	if p1.type == myHero.type then
		p1 = p1.visionPos
	end
	if p1.type == myHero.type then
		p2 = p2.visionPos
	end

	return math.sqrt(GetDistanceSqr(p1, p2))
end

function _Helper:StringContains(string, contains)
	return string:lower():find(contains)
end

function _Helper:DrawCircleObject(Object, Range, Colour, Thickness)
	if not Object then return end
	Thickness = Thickness and Thickness or 0
	for i = 0, Thickness do
		if DrawingMenu.LowFPS then
			self:DrawCircle2(Object.x, Object.y, Object.z, Range + i, Colour)
		else
			DrawCircle(Object.x, Object.y, Object.z, Range + i, Colour)
		end
	end
end

-- Low fps circles by barasia, vadash and viseversa
function _Helper:DrawCircleNextLvl(x, y, z, radius, width, color, chordlength)
    radius = radius or 300
		quality = math.max(8,self:round(180/math.deg((math.asin((chordlength/(2*radius)))))))
		quality = 2 * math.pi / quality
		radius = radius*.92
    local points = {}
    for theta = 0, 2 * math.pi + quality, quality do
        local c = WorldToScreen(D3DXVECTOR3(x + radius * math.cos(theta), y, z - radius * math.sin(theta)))
        points[#points + 1] = D3DXVECTOR2(c.x, c.y)
    end
    if DrawLines2 then
    	DrawLines2(points, width or 1, color or 4294967295)
    end
end

function _Helper:round(num) 
	if num >= 0 then return math.floor(num+.5) else return math.ceil(num-.5) end
end

function _Helper:DrawCircle2(x, y, z, radius, color)
    local vPos1 = Vector(x, y, z)
    local vPos2 = Vector(cameraPos.x, cameraPos.y, cameraPos.z)
    local tPos = vPos1 - (vPos1 - vPos2):normalized() * radius
    local sPos = WorldToScreen(D3DXVECTOR3(tPos.x, tPos.y, tPos.z))
    if OnScreen({ x = sPos.x, y = sPos.y }, { x = sPos.x, y = sPos.y }) then
        self:DrawCircleNextLvl(x, y, z, radius, 1, color, 75)	
    end
end

function PrintSystemMessage(Message)
	PrintChat(tostring("<font color='#D859CD'>Sida's Auto Carry: Reborn - </font><font color='#adec00'> "..Message.."</font>"))
end

function _Helper:GetHitBoxDistance(Target)
	return Helper:GetDistance(Target) - Helper:GetDistance(Target, Target.minBBox)
end

function _Helper:TrimString(s)
	return s:find'^%s*$' and '' or s:match'^%s*(.*%S)'
end

function _Helper:GetClasses()
	return AutoCarry.Skills, AutoCarry.Keys, AutoCarry.Items, AutoCarry.Data, AutoCarry.Jungle, AutoCarry.Helper, AutoCarry.MyHero, AutoCarry.Minions, AutoCarry.Crosshair, AutoCarry.Orbwalker
end

function _Helper:ArgbFromMenu(menu)
	return ARGB(menu[1], menu[2], menu[3], menu[4])
end

function _Helper:DecToHex(Dec)
	local B, K, Hex, I, D = 16, "0123456789ABCDEF", "", 0
	while Dec > 0 do
		I = I + 1
		Dec, D = math.floor(Dec / B), math.fmod(Dec, B) + 1
		Hex = string.sub(K, D, D)..Hex
	end
	return Hex
end

function _Helper:HexFromMenu(menu)
	local argb = {}
	argb["a"] = menu[1]
	argb["r"] = menu[2]
	argb["g"] = menu[3]
	argb["b"] = menu[4]
	return tonumber(self:DecToHex(argb["a"]) .. self:DecToHex(argb["r"]) .. self:DecToHex(argb["g"]) .. self:DecToHex(argb["b"]), 16);
end

function _Helper:IsEvading()
	return _G.evade or _G.Evade
end

function _Helper:GetAllHeroes()
	for i = 1, heroManager.iCount do
        local hero = heroManager:GetHero(i)
        table.insert(self.AllHeroes, hero)
    end
end

function _Helper:Debug(str)
	table.insert(self.DebugStrings, str)
end

function _Helper:_OnDraw()
	local Height = 200
	for _, Str in pairs(self.DebugStrings) do
		DrawText(tostring(Str), 15, 100, Height, 0xFFFFFF00)
		Height = Height + 20
	end
	self.DebugStrings = {}
end

function _Helper:CountAlliesInRange(Range)
	local _Count = 0
	for _, Ally in pairs(GetAllyHeroes()) do
		if Ally ~= myHero and Helper:GetDistance(Ally) <= Range then
			_Count = _Count + 1
		end
	end
	return _Count
end

--[[
		_Wards Class
]]

class '_Wards' Wards = nil

function _Wards:__init()
	self.EnemyWards = {}
	self.IncomingWards = {}
	self.AllyIncomingWards = {}
	self.PlacedWards = {}
	
	AddTickCallback(function() self:_OnTick() end)
	AddCreateObjCallback(function(Obj) self:_OnCreateObj(Obj) end)
	AddDeleteObjCallback(function(Obj) self:_OnDeleteObj(Obj) end)
	AddProcessSpellCallback(function(Unit, Spell) self:_OnProcessSpell(Unit, Spell) end)
	AddRecvPacketCallback(function(Packet) self:_OnReceivePacket(Packet) end)
	AdvancedCallback:bind("OnGainFocs")
	Plugins:RegisterPreAttack(function(target) self:PreAttack(target) end)
	Wards = self
end

function _Wards:_OnReceivePacket(p)
	if p.header == 49 then -- delete packet
		p.pos = 1
		local deaddid = p:DecodeF()
		local killerid = p:DecodeF()
		for networkID, ward in pairs(self.PlacedWards) do
			if ward and deaddid and networkID == deaddid and ward.vanga == 1 and (GetTickCount() - ward.spawnTime) > 200 then
				self.PlacedWards[networkID] = nil
			elseif ward and deaddid and networkID == deaddid and ward.vanga == 2 and killerid == 0 then
				self.PlacedWards[networkID] = nil
			end
		end
	end
	
	if p.header == 0xB4 then -- create packet
		p.pos = 12
		local wardtype2 = p:Decode1()
		p.pos = 1
		local creatorID = p:DecodeF()
		p.pos = p.pos + 20
		local creatorID2 = p:DecodeF()
		p.pos = 37
		local objectID = p:DecodeF()
		local objectX = p:DecodeF()
		local objectY = p:DecodeF()
		local objectZ = p:DecodeF()
		local objectX2 = p:DecodeF()
		local objectY2 = p:DecodeF()
		local objectZ2 = p:DecodeF()
		p:DecodeF()
		local warddet = p:Decode1()
		p.pos = p.pos + 4
		local warddet2 = p:Decode1()
		p.pos = 13
		local wardtype = p:Decode1()

		local objectID = DwordToFloat(AddNum(FloatToDword(objectID), 2))
		local creatorchamp = objManager:GetObjectByNetworkId(creatorID)
		local duration
		local range
		if creatorchamp and creatorchamp.team ~= myHero.team then return end
		
		if warddet == 0x3F and warddet2 == 0x33 and wardtype ~= 12 and wardtype ~= 48 then --wards 116 | wardtype 48 -> riven E
			if wardtype2 == 0x6E then
				self.PlacedWards[objectID] = {x = objectX2, y = objectY2, z = objectZ2, visionRange = 1100, spawnTime = GetTickCount(), duration = 60000, vanga = 1 }	-- WARDING TOTEM
			elseif wardtype2 == 0x2E then
				self.PlacedWards[objectID] = {x = objectX2, y = objectY2, z = objectZ2, visionRange = 1100, spawnTime = GetTickCount(), duration = 120000, vanga = 1 }	-- GREATER TOTEM
			elseif wardtype2 == 0xAE then
				self.PlacedWards[objectID] = {x = objectX2, y = objectY2, z = objectZ2, visionRange = 1100, spawnTime = GetTickCount(), duration = 180000, vanga = 1 }	-- GREATER STEALTH TOTEM
			elseif wardtype2 == 0xEE then
				self.PlacedWards[objectID] = {x = objectX2, y = objectY2, z = objectZ2, visionRange = 1100, spawnTime = GetTickCount(), duration = 180000, vanga = 1 }	-- WRIGGLES LANTERN
			elseif (wardtype==8 or wardtype2==0x7E) then
				self.PlacedWards[objectID] = {x = objectX2, y = objectY2, z = objectZ2, visionRange = 1100, spawnTime = GetTickCount(), duration = 180000, vanga = 1 } -- VISION
			else
				self.PlacedWards[objectID] = {x = objectX2, y = objectY2, z = objectZ2, visionRange = 1100, spawnTime = GetTickCount(), duration = 180000, vanga = 1 }
			end
		end
	end
	p.pos = 1
end

function _Wards:_OnTick()
	if self.LastWardAttacked then
		if GetTickCount() > self.LastWardAttacked.Time + 100 and self.LastWardAttacked.LastAttack == Orbwalker.LastAttack then
			for i, Ward in pairs(self.EnemyWards) do
				if Ward == self.LastWardAttacked.Object then
					table.remove(self.EnemyWards, i)
					self.LastWardAttacked = nil
					return
				end
			end
		end
	end
end

function _Wards:_OnProcessSpell(Unit, Spell)
	if Data:IsWardSpell(Spell) then
		if Unit.team ~= myHero.team then
			table.insert(self.IncomingWards, Spell.endPos)
		else
			table.insert(self.AllyIncomingWards, Spell.endPos)
		end
	end
end

function _Wards:_OnCreateObj(Obj)
	if Obj and Data:IsWard(Obj) then
		for i, Inc in pairs(self.IncomingWards) do
			if Helper:GetDistance(Inc, Obj) < 50 then
				table.insert(self.EnemyWards, Obj)
				table.remove(self.IncomingWards, i)
				return
			end
		end
		for i, Inc in pairs(self.PlacedWards) do
			if Helper:GetDistance(Inc, Obj) < 50 then
				table.remove(self.AllyIncomingWards, i)
				return
			end
		end
		-- if VIP_USER then
			table.insert(self.EnemyWards, Obj)
		-- end
	end
end

function _Wards:PreAttack(Target)
	if not self.LastWardAttacked then
		for _, Ward in pairs(self.EnemyWards) do
			if Ward == Target then
				self.LastWardAttacked = {Time = GetTickCount(), LastAttack = Orbwalker.LastAttack, Object = Ward}
			end
		end
	end
end

function _Wards:_OnDeleteObj(Obj)
	for i, Ward in pairs(self.EnemyWards) do
		if Obj == Ward then
			table.remove(self.EnemyWards, i)
		end
	end
end

function _Wards:GetAttackableWard()
	for _, Ward in pairs(self.EnemyWards) do
		if Helper:GetDistance(Ward) < MyHero.TrueRange and Ward.visible and not Ward.dead then
			return Ward
		end
	end
end

--[[
		_Keys Class
]]

class '_Keys' Keys = nil

function _Keys:__init()
	self.KEYS_KEY = 0
	self.KEYS_MENUKEY = 1
	self.AutoCarry = false
	self.MixedMode = false
	self.LastHit = false
	self.LaneClear = false
	self.AutoCarryKeys = {}
	self.MixedModeKeys = {}
	self.LastHitKeys = {}
	self.LaneClearKeys = {}
	self.LMouseDown = false
	self.AutoCarryKeyDown = false
	self.MixedModeKeyDown = false
	self.LaneClearKeyDown = false
	self.LastHitKeyDown = false
	Keys = self

	AddTickCallback(function() self:_OnTick() end)
	AddMsgCallback(function(Msg, Key) self:_OnWndMsg(Msg, Key) end)
end

function _Keys:_OnTick()
	self.AutoCarry = self:IsKeyEnabled(self.AutoCarryKeys)
	self.MixedMode = self:IsKeyEnabled(self.MixedModeKeys)
	self.LastHit = self:IsKeyEnabled(self.LastHitKeys)
	self.LaneClear = self:IsKeyEnabled(self.LaneClearKeys)
	self:ModeKeyPressed()
end

function _Keys:ModeKeyPressed()
	if self.AutoCarryKeyDown and not AutoCarryMenu.Active and not AutoCarryMenu.Toggle then
		self:EnableMode(MODE_AUTOCARRY)
	elseif self.MixedModeKeyDown and not MixedModeMenu.Active and not MixedModeMenu.Toggle then
		self:EnableMode(MODE_MIXEDMODE)
	elseif self.LaneClearKeyDown and not LaneClearMenu.Active and not LaneClearMenu.Toggle then
		self:EnableMode(MODE_LANECLEAR)
	elseif self.LastHitKeyDown and not LastHitMenu.Active and not LastHitMenu.Toggle then
		self:EnableMode(MODE_LASTHIT)
	end
end

function _Keys:EnableMode(Mode)
	AutoCarryMenu.Active = (Mode == MODE_AUTOCARRY and true or false)
	MixedModeMenu.Active = (Mode == MODE_MIXEDMODE and true or false)
	LaneClearMenu.Active = (Mode == MODE_LANECLEAR and true or false)
	LastHitMenu.Active = (Mode == MODE_LASTHIT and true or false)
end

function _Keys:_OnWndMsg(Msg, Key)
	if Msg == WM_LBUTTONDOWN then
		self.LMouseDown = true
	elseif Msg == WM_LBUTTONUP then
		self.LMouseDown = false
	elseif Msg == KEY_DOWN then
		if Key == AutoCarryMenu._param[5].key then
			self.AutoCarryKeyDown = true
		elseif Key == MixedModeMenu._param[3].key then
			self.MixedModeKeyDown = true
		elseif Key == LaneClearMenu._param[3].key then
			self.LaneClearKeyDown = true
		elseif Key == LastHitMenu._param[3].key then
			self.LastHitKeyDown = true
		end
	elseif Msg == KEY_UP then
		if Key == AutoCarryMenu._param[5].key then
			self.AutoCarryKeyDown = false
		elseif Key == MixedModeMenu._param[3].key then
			self.MixedModeKeyDown = false
		elseif Key == LaneClearMenu._param[3].key then
			self.LaneClearKeyDown = false
		elseif Key == LastHitMenu._param[3].key then
			self.LastHitKeyDown = false
		end
	end
end

function _Keys:IsKeyEnabled(List)
	for _, Key in pairs(List) do
		if Key.Type == self.KEYS_KEY then
			if IsKeyDown(Key.Key) then
				return true
			end
		elseif Key.Type == self.KEYS_MENUKEY then
			if Key.Menu[Key.Param] then
				return true
			end
		end
	end
	
	if List == self.AutoCarryKeys and AutoCarryMenu and AutoCarryMenu.LeftClick and self.LMouseDown then
		return true		
	end
	
	return false
end

function _Keys:RegisterMenuKey(Menu, Param, Mode)
	local MenuKey = _MenuKey(Menu, Param)
	self:Insert(MenuKey, Mode)
end

function _Keys:RegisterKey(key, Mode)
	local Key = _Key(key)
	self:Insert(Key, Mode)
end

function _Keys:UnregisterKey(_Key, Mode)
	for i, Key in pairs(self:GetKeyList(Mode)) do
		if Key.Key == _Key then
			table.remove(self:GetKeyList(Mode), i)
		end
	end
end

function _Keys:Insert(Key, Mode)
	if Mode == MODE_AUTOCARRY then
		table.insert(self.AutoCarryKeys, Key)
	elseif Mode == MODE_MIXEDMODE then
		table.insert(self.MixedModeKeys, Key)
	elseif Mode == MODE_LASTHIT then
		table.insert(self.LastHitKeys, Key)
	elseif Mode == MODE_LANECLEAR then
		table.insert(self.LaneClearKeys, Key)
	end
end

function _Keys:GetKeyList(Mode)
	if Mode == MODE_AUTOCARRY then
		return self.AutoCarryKeys
	elseif Mode == MODE_MIXEDMODE then
		return self.MixedModeKeys
	elseif Mode == MODE_LASTHIT then
		return self.LastHitKeys
	elseif Mode == MODE_LANECLEAR then
		return self.LaneClearKeys
	end
end


--[[
		Key Class
]]

class '_Key'

function _Key:__init(key)
	self.Key = key
	self.Type = Keys.KEYS_KEY
end

--[[
		MenuKey Class
]]

class '_MenuKey'

function _MenuKey:__init(menu, param)
	self.Menu = menu
	self.Param = param
	self.Type = Keys.KEYS_MENUKEY
end

--[[
		_SwingTimer Class
]]

class '_SwingTimer' SwingTimer = nil

function _SwingTimer:__init()
	self.bar_green = GetSprite("SidasAutoCarry\\bar_green.dds")
	self.bar_red = GetSprite("SidasAutoCarry\\bar_red.dds")
	self.Width = 300
	self.X = WINDOW_W/2 - (self.Width/2)
	self.Y = WINDOW_H/2
	self.Height = self.bar_green.height	
	self.ResizeButtonWidth = 30
	self.Moving = false
	self.Resizing = false
	self.Save = GetSave("SidasAutoCarry").SwingTimer
	SwingTimer = self

	if self.Save then
		self.X = self.Save.X
		self.Y = self.Save.Y
		self.Width = self.Save.Width
	end
	AddMsgCallback(function(Msg, Key) self:_OnWndMsg(Msg, Key) end)
	AddDrawCallback(function() self:_OnDraw() end)
	AddTickCallback(function() self:_OnTick() end)
end

function _SwingTimer:_OnDraw()
	if not ExtrasMenu.ShowSwingTimer then return end
	local Difference = (Helper.Tick - Orbwalker.LastAttack) / (Orbwalker:GetNextAttackTime() - Orbwalker.LastAttack)
	if Difference > 1 or Difference < 0 then 
		self:_DrawBar(self.X, self.Y, self.Width, 1)
	else
		self:_DrawBar(self.X, self.Y, self.Width, Difference)
	end
	if IsKeyDown(16) then
		DrawRectangleOutline(self.X + self.Width + 5, self.Y, self.ResizeButtonWidth, self.Height, ARGB(0xFF,0xFF,0xFF,0xFF), 3)
	end
end

function _SwingTimer:_OnWndMsg(Msg, Key)
	if Msg == WM_LBUTTONDOWN and IsKeyDown(16) then
		if CursorIsUnder(self.X, self.Y, self.Width, self.Height) then
			self.Moving = true
		elseif CursorIsUnder(self.X + self.Width + 5, self.Y, self.ResizeButtonWidth, self.Height) then
			self.Resizing = true
		end
	elseif Msg == WM_LBUTTONUP and (self.Moving or self.Resizing) then
		self.Moving = false
		self.Resizing = false
		GetSave("SidasAutoCarry").SwingTimer = {X = self.X, Y = self.Y, Height = self.Height, Width = self.Width}
	end
end

function _SwingTimer:_OnTick()
	if self.Moving then
		self.X = GetCursorPos().x - self.Width / 2
		self.Y = GetCursorPos().y
	elseif self.Resizing then
		self.Width = GetCursorPos().x - self.X - self.ResizeButtonWidth / 2
	end
end

function _SwingTimer:_DrawBar(x, y, barLen, percentage)
	DrawRectangleOutline(x-1, y-1, barLen+3, self.Height+2, ARGB(0x00,0xFF,0xFF,0xFF), 1)
    self.bar_green:DrawEx(Rect(0, 0, barLen*percentage, self.Height), D3DXVECTOR3(0,0,0), D3DXVECTOR3(x,y,0), 0xFF)
    self.bar_red:DrawEx(Rect(barLen*percentage, 0, barLen, self.Height), D3DXVECTOR3(0,0,0), D3DXVECTOR3(x+barLen*percentage,y,0), 0xFF)
    DrawTextA(math.floor(percentage*100).."%", 12, x+(barLen/2), y-1, ARGB(255,255,255,255), "center")
end

--[[
		_Streaming Class
]]

class '_Streaming'

function _Streaming:__init()
	self.Save = GetSave("SidasAutoCarry")

	AddTickCallback(function()self:_OnTick() end)
	AddMsgCallback(function(msg, key)self:_OnWndMsg(msg, key) end)

	if self.Save.StreamingMode then
		self:EnableStreaming()
	else
		self:DisableStreaming()
	end
end

function _Streaming:_OnTick()
	if self.StreamingMenu then
		self.StreamingMenu._param[4].text = self.StreamingMenu.Colour == 0 and "Green" or "Red"
	end
	if self.StreamEnabled then
		self:EnableStreaming()
	end
end

function _Streaming:_OnWndMsg(msg, key)
	if msg == KEY_DOWN and key == 118 then
		if not self.StreamEnabled then
			self:EnableStreaming()
		else
			self:DisableStreaming()
		end
	end
end

function _Streaming:EnableStreaming()
	self.Save.StreamingMode = true
	self.StreamEnabled = true
	if not self.ChatTimeout then
		self.ChatTimeout = GetTickCount() + 3000
	elseif GetTickCount() < self.ChatTimeout then
		self:DisableOverlay()
		for i = 0, 15 do
			PrintChat("")
		end
	else
		_G.PrintChat = function() end
	end
end

function _Streaming:DisableStreaming()
	self.Save.StreamingMode = false
	self.StreamEnabled = false
	if self.ChatTimeout then
		EnableOverlay()
		self.ChatTimeout = nil
	end
end

function _Streaming:OnMove()
	-- if not self.MenuCreated then return end
	-- if self.StreamingMenu.MinRand > self.StreamingMenu.MaxRand then
	-- 	print("Reborn: Min cannot be higher than Max in Streaming Menu")
	-- elseif self.StreamingMenu.ShowClick and (not self.NextClick or Helper.Tick > self.NextClick) then
	-- 	if self.StreamingMenu.Colour == 0 then
	-- 		ShowGreenClick(mousePos)
	-- 	else
	-- 		ShowRedClick(mousePos)
	-- 	end
	-- 	self.nextClick = Helper.Tick + math.random(self.StreamingMenu.MinRand, self.StreamingMenu.MaxRand)
	-- end
end

function _Streaming:CreateMenu()
	self.StreamingMenu = scriptConfig("Sida's Auto Carry: Streaming", "sidasacstreaming")
	self.StreamingMenu:addParam("ShowClick", "Show Fake Click Marker", SCRIPT_PARAM_ONOFF, false)
	self.StreamingMenu:addParam("MinRand", "Minimum Time Between Clicks", SCRIPT_PARAM_SLICE, 150, 0, 1000, 0)
	self.StreamingMenu:addParam("MaxRand", "Maximum Time Between Clicks", SCRIPT_PARAM_SLICE, 650, 0, 1000, 0)
	self.StreamingMenu:addParam("Colour", "Green", SCRIPT_PARAM_SLICE, 0, 0, 1, 0)
	self.StreamingMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	self.StreamingMenu:addParam("sep", "Toggle Streaming Mode with F7", SCRIPT_PARAM_INFO, "")
	self.MenuCreated = true
end

function _Streaming:DisableOverlay()
    _G.DrawText, 
    _G.PrintFloatText, 
    _G.DrawLine, 
    _G.DrawArrow, 
    _G.DrawCircle, 
    _G.DrawRectangle, 
    _G.DrawLines, 
    _G.DrawLines2 = function() end, 
    function() end, 
    function() end, 
    function() end, 
    function() end, 
    function() end, 
    function() end
end

Streaming = _Streaming()
--[[ 
		_Summoner Class
]]

class '_Summoners'

function _Summoners:__init()
	self.Barrier = (myHero:GetSpellData(SUMMONER_1).name:find("SummonerBarrier") and {Slot = SUMMONER_1} or (myHero:GetSpellData(SUMMONER_2).name:find("SummonerBarrier") and {Slot = SUMMONER_2} or nil))
	self.Ignite = (myHero:GetSpellData(SUMMONER_1).name:find("SummonerDot") and {Slot = SUMMONER_1, Range = 500} or (myHero:GetSpellData(SUMMONER_2).name:find("SummonerDot") and {Slot = SUMMONER_2, Range = 500} or nil))

	AddProcessSpellCallback(function(Unit, Spell) self:_OnProcessSpell(Unit, Spell) end)
	AddTickCallback(function() self:_OnTick() end)
end

function _Summoners:_OnTick()
	if self.Ignite and SummonersMenu.Ignite then
		for _, Enemy in pairs(Helper.EnemyTable) do
			if Helper:GetDistance(Enemy) <= 500 and Enemy.health < getDmg("IGNITE", Enemy, myHero) then
				CastSpell(self.Ignite.Slot, Enemy)
			end
		end
	end
end

function _Summoners:_OnProcessSpell(object,spell)
	if not self.Barrier or not SummonersMenu.Barrier then return end
	if object.team ~= myHero.team and not myHero.dead and not (object.name:find("Minion_") or object.name:find("Odin")) then
		local sbarrierREADY = self.Barrier.Slot ~= nil and myHero:CanUseSpell(self.Barrier.Slot) == READY
		local shealREADY = sheal ~= nil and myHero:CanUseSpell(sheal) == READY
		local lisslot = GetInventorySlotItem(3190)
		local seslot = GetInventorySlotItem(3040)
		local lisREADY = lisslot ~= nil and myHero:CanUseSpell(lisslot) == READY
		local seREADY = seslot ~= nil and myHero:CanUseSpell(seslot) == READY
		local HitFirst = false
		local shieldtarget,SLastDistance,SLastDmgPercent = nil,nil,nil
		local healtarget,HLastDistance,HLastDmgPercent = nil,nil,nil
		local ulttarget,ULastDistance,ULastDmgPercent = nil,nil,nil
		BShield,SShield,Shield,CC = false,false,false,false
		shottype,radius,maxdistance = 0,0,0
		if object.type == "obj_AI_Hero" then
			spelltype, casttype = getSpellType(object, spell.name)
			if casttype == 4 or casttype == 5 then return end
			if spelltype == "BAttack" or spelltype == "CAttack" or spell.name:find("SummonerDot") then
				Shield = true
			elseif spelltype == "Q" or spelltype == "W" or spelltype == "E" or spelltype == "R" or spelltype == "P" or spelltype == "QM" or spelltype == "WM" or spelltype == "EM" then
				HitFirst = skillShield[object.charName][spelltype]["HitFirst"]
				BShield = skillShield[object.charName][spelltype]["BShield"]
				SShield = skillShield[object.charName][spelltype]["SShield"]
				Shield = skillShield[object.charName][spelltype]["Shield"]
				CC = skillShield[object.charName][spelltype]["CC"]
				shottype = skillData[object.charName][spelltype]["type"]
				radius = skillData[object.charName][spelltype]["radius"]
				maxdistance = skillData[object.charName][spelltype]["maxdistance"]
			end
		else
			Shield = true
		end
		for i=1, heroManager.iCount do
			local allytarget = heroManager:GetHero(i)
			if allytarget.team == myHero.team and not allytarget.dead and allytarget.health > 0 then
				hitchampion = false
				local allyHitBox = self:getHitBox(allytarget)
				if shottype == 0 then hitchampion = spell.target and spell.target.networkID == allytarget.networkID
				elseif shottype == 1 then hitchampion = checkhitlinepass(object, spell.endPos, radius, maxdistance, allytarget, allyHitBox)
				elseif shottype == 2 then hitchampion = checkhitlinepoint(object, spell.endPos, radius, maxdistance, allytarget, allyHitBox)
				elseif shottype == 3 then hitchampion = checkhitaoe(object, spell.endPos, radius, maxdistance, allytarget, allyHitBox)
				elseif shottype == 4 then hitchampion = checkhitcone(object, spell.endPos, radius, maxdistance, allytarget, allyHitBox)
				elseif shottype == 5 then hitchampion = checkhitwall(object, spell.endPos, radius, maxdistance, allytarget, allyHitBox)
				elseif shottype == 6 then hitchampion = checkhitlinepass(object, spell.endPos, radius, maxdistance, allytarget, allyHitBox) or checkhitlinepass(object, Vector(object)*2-spell.endPos, radius, maxdistance, allytarget, allyHitBox)
				elseif shottype == 7 then hitchampion = checkhitcone(spell.endPos, object, radius, maxdistance, allytarget, allyHitBox)
				end
				if hitchampion then
					if sbarrierREADY and allytarget.isMe and Shield then
						local barrierflag, dmgpercent = self:shieldCheck(object,spell,allytarget,"barrier")
						if barrierflag then
							CastSpell(self.Barrier.Slot)
						end
					end
				end
			end
		end
		if shieldtarget ~= nil then
			if typeshield==1 or typeshield==5 then CastSpell(spellslot,shieldtarget)
			elseif typeshield==2 or typeshield==4 then CastSpell(spellslot,shieldtarget.x,shieldtarget.z)
			elseif typeshield==3 or typeshield==6 then CastSpell(spellslot) end
		end
		if healtarget ~= nil then
			if typeheal==1 then CastSpell(healslot,healtarget)
			elseif typeheal==2 or typeheal==3 then CastSpell(healslot) end
		end
		if ulttarget ~= nil then
			if typeult==1 or typeult==3 then CastSpell(ultslot,ulttarget)
			elseif typeult==2 or typeult==4 then CastSpell(ultslot) end		
		end
	end	
end

function _Summoners:shieldCheck(object,spell,target,typeused)
	local configused
	if typeused == "shields" then configused = ASConfig
	elseif typeused == "heals" then configused = AHConfig
	elseif typeused == "ult" then configused = AUConfig
	elseif typeused == "barrier" then configused = ASBConfig 
	elseif typeused == "sheals" then configused = ASHConfig
	elseif typeused == "items" then configused = ASIConfig end
	local shieldflag = false
	local adamage = object:CalcDamage(target,object.totalDamage)
	local InfinityEdge,onhitdmg,onhittdmg,onhitspelldmg,onhitspelltdmg,muramanadmg,skilldamage,skillTypeDmg = 0,0,0,0,0,0,0,0

	if object.type ~= "obj_AI_Hero" then
		if spell.name:find("BasicAttack") then skilldamage = adamage
		elseif spell.name:find("CritAttack") then skilldamage = adamage*2 end
	else
		if GetInventoryHaveItem(3186,object) then onhitdmg = getDmg("KITAES",target,object) end
		if GetInventoryHaveItem(3114,object) then onhitdmg = onhitdmg+getDmg("MALADY",target,object) end
		if GetInventoryHaveItem(3091,object) then onhitdmg = onhitdmg+getDmg("WITSEND",target,object) end
		if GetInventoryHaveItem(3057,object) then onhitdmg = onhitdmg+getDmg("SHEEN",target,object) end
		if GetInventoryHaveItem(3078,object) then onhitdmg = onhitdmg+getDmg("TRINITY",target,object) end
		if GetInventoryHaveItem(3100,object) then onhitdmg = onhitdmg+getDmg("LICHBANE",target,object) end
		if GetInventoryHaveItem(3025,object) then onhitdmg = onhitdmg+getDmg("ICEBORN",target,object) end
		if GetInventoryHaveItem(3087,object) then onhitdmg = onhitdmg+getDmg("STATIKK",target,object) end
		if GetInventoryHaveItem(3153,object) then onhitdmg = onhitdmg+getDmg("RUINEDKING",target,object) end
		if GetInventoryHaveItem(3209,object) then onhittdmg = getDmg("SPIRITLIZARD",target,object) end
		if GetInventoryHaveItem(3184,object) then onhittdmg = onhittdmg+80 end
		if GetInventoryHaveItem(3042,object) then muramanadmg = getDmg("MURAMANA",target,object) end
		if spelltype == "BAttack" then
			skilldamage = (adamage+onhitdmg+muramanadmg)*1.07+onhittdmg
		elseif spelltype == "CAttack" then
			if GetInventoryHaveItem(3031,object) then InfinityEdge = .5 end
			skilldamage = (adamage*(2.1+InfinityEdge)+onhitdmg+muramanadmg)*1.07+onhittdmg --fix Lethality
		elseif spelltype == "Q" or spelltype == "W" or spelltype == "E" or spelltype == "R" or spelltype == "P" or spelltype == "QM" or spelltype == "WM" or spelltype == "EM" then
			if GetInventoryHaveItem(3151,object) then onhitspelldmg = getDmg("LIANDRYS",target,object) end
			if GetInventoryHaveItem(3188,object) then onhitspelldmg = getDmg("BLACKFIRE",target,object) end
			if GetInventoryHaveItem(3209,object) then onhitspelltdmg = getDmg("SPIRITLIZARD",target,object) end
			muramanadmg = skillShield[object.charName][spelltype]["Muramana"] and muramanadmg or 0
			if casttype == 1 then
				skilldamage, skillTypeDmg = getDmg(spelltype,target,object,1,spell.level)
			elseif casttype == 2 then
				skilldamage, skillTypeDmg = getDmg(spelltype,target,object,2,spell.level)
			elseif casttype == 3 then
				skilldamage, skillTypeDmg = getDmg(spelltype,target,object,3,spell.level)
			end
			if skillTypeDmg == 2 then
				skilldamage = (skilldamage+adamage+onhitspelldmg+onhitdmg+muramanadmg)*1.07+onhittdmg+onhitspelltdmg
			else
				if skilldamage > 0 then skilldamage = (skilldamage+onhitspelldmg+muramanadmg)*1.07+onhitspelltdmg end
			end
		elseif spell.name:find("SummonerDot") then
			skilldamage = getDmg("IGNITE",target,object)
		end
	end
	local dmgpercent = skilldamage*100/target.health
	local dmgneeded = dmgpercent >= 95
	local hpneeded = 100 >= (target.health-skilldamage)*100/target.maxHealth
	
	if dmgneeded and hpneeded then
		shieldflag = true
	elseif typeused == "shields" and ((CC == 2 and configused.shieldcc) or (CC == 1 and configused.shieldslow)) then
		shieldflag = true
	end
	return shieldflag, dmgpercent
end
function _Summoners:getHitBox(hero)
    local hitboxTable = { ['HeimerTGreen'] = 50.0, ['Darius'] = 80.0, ['ZyraGraspingPlant'] = 20.0, ['HeimerTRed'] = 50.0, ['ZyraThornPlant'] = 20.0, ['Nasus'] = 80.0, ['HeimerTBlue'] = 50.0, ['SightWard'] = 1, ['HeimerTYellow'] = 50.0, ['Kennen'] = 55.0, ['VisionWard'] = 1, ['ShacoBox'] = 10, ['HA_AP_Poro'] = 0, ['TempMovableChar'] = 48.0, ['TeemoMushroom'] = 50.0, ['OlafAxe'] = 50.0, ['OdinCenterRelic'] = 48.0, ['Blue_Minion_Healer'] = 48.0, ['AncientGolem'] = 100.0, ['AnnieTibbers'] = 80.0, ['OdinMinionGraveyardPortal'] = 1.0, ['OriannaBall'] = 48.0, ['LizardElder'] = 65.0, ['YoungLizard'] = 50.0, ['OdinMinionSpawnPortal'] = 1.0, ['MaokaiSproutling'] = 48.0, ['FizzShark'] = 0, ['Sejuani'] = 80.0, ['Sion'] = 80.0, ['OdinQuestIndicator'] = 1.0, ['Zac'] = 80.0, ['Red_Minion_Wizard'] = 48.0, ['DrMundo'] = 80.0, ['Blue_Minion_Wizard'] = 48.0, ['ShyvanaDragon'] = 80.0, ['HA_AP_OrderShrineTurret'] = 88.4, ['Heimerdinger'] = 55.0, ['Rumble'] = 80.0, ['Ziggs'] = 55.0, ['HA_AP_OrderTurret3'] = 88.4, ['HA_AP_OrderTurret2'] = 88.4, ['TT_Relic'] = 0, ['Veigar'] = 55.0, ['HA_AP_HealthRelic'] = 0, ['Teemo'] = 55.0, ['Amumu'] = 55.0, ['HA_AP_ChaosTurretShrine'] = 88.4, ['HA_AP_ChaosTurret'] = 88.4, ['HA_AP_ChaosTurretRubble'] = 88.4, ['Poppy'] = 55.0, ['Tristana'] = 55.0, ['HA_AP_PoroSpawner'] = 50.0, ['TT_NGolem'] = 80.0, ['HA_AP_ChaosTurretTutorial'] = 88.4, ['Volibear'] = 80.0, ['HA_AP_OrderTurretTutorial'] = 88.4, ['TT_NGolem2'] = 80.0, ['HA_AP_ChaosTurret3'] = 88.4, ['HA_AP_ChaosTurret2'] = 88.4, ['Shyvana'] = 50.0, ['HA_AP_OrderTurret'] = 88.4, ['Nautilus'] = 80.0, ['ARAMOrderTurretNexus'] = 88.4, ['TT_ChaosTurret2'] = 88.4, ['TT_ChaosTurret3'] = 88.4, ['TT_ChaosTurret1'] = 88.4, ['ChaosTurretGiant'] = 88.4, ['ARAMOrderTurretFront'] = 88.4, ['ChaosTurretWorm'] = 88.4, ['OdinChaosTurretShrine'] = 88.4, ['ChaosTurretNormal'] = 88.4, ['OrderTurretNormal2'] = 88.4, ['OdinOrderTurretShrine'] = 88.4, ['OrderTurretDragon'] = 88.4, ['OrderTurretNormal'] = 88.4, ['ARAMChaosTurretFront'] = 88.4, ['ARAMOrderTurretInhib'] = 88.4, ['ChaosTurretWorm2'] = 88.4, ['TT_OrderTurret1'] = 88.4, ['TT_OrderTurret2'] = 88.4, ['ARAMChaosTurretInhib'] = 88.4, ['TT_OrderTurret3'] = 88.4, ['ARAMChaosTurretNexus'] = 88.4, ['OrderTurretAngel'] = 88.4, ['Mordekaiser'] = 80.0, ['TT_Buffplat_R'] = 0, ['Lizard'] = 50.0, ['GolemOdin'] = 80.0, ['Renekton'] = 80.0, ['Maokai'] = 80.0, ['LuluLadybug'] = 50.0, ['Alistar'] = 80.0, ['Urgot'] = 80.0, ['LuluCupcake'] = 50.0, ['Gragas'] = 80.0, ['Skarner'] = 80.0, ['Yorick'] = 80.0, ['MalzaharVoidling'] = 10.0, ['LuluPig'] = 50.0, ['Blitzcrank'] = 80.0, ['Chogath'] = 80.0, ['Vi'] = 50, ['FizzBait'] = 0, ['Malphite'] = 80.0, ['EliseSpiderling'] = 1.0, ['Dragon'] = 100.0, ['LuluSquill'] = 50.0, ['Worm'] = 100.0, ['redDragon'] = 100.0, ['LuluKitty'] = 50.0, ['Galio'] = 80.0, ['Annie'] = 55.0, ['EliseSpider'] = 50.0, ['SyndraSphere'] = 48.0, ['LuluDragon'] = 50.0, ['Hecarim'] = 80.0, ['TT_Spiderboss'] = 200.0, ['Thresh'] = 55.0, ['ARAMChaosTurretShrine'] = 88.4, ['ARAMOrderTurretShrine'] = 88.4, ['Blue_Minion_MechMelee'] = 65.0, ['TT_NWolf'] = 65.0, ['Tutorial_Red_Minion_Wizard'] = 48.0, ['YorickRavenousGhoul'] = 1.0, ['SmallGolem'] = 80.0, ['OdinRedSuperminion'] = 55.0, ['Wraith'] = 50.0, ['Red_Minion_MechCannon'] = 65.0, ['Red_Minion_Melee'] = 48.0, ['OdinBlueSuperminion'] = 55.0, ['TT_NWolf2'] = 50.0, ['Tutorial_Red_Minion_Basic'] = 48.0, ['YorickSpectralGhoul'] = 1.0, ['Wolf'] = 50.0, ['Blue_Minion_MechCannon'] = 65.0, ['Golem'] = 80.0, ['Blue_Minion_Basic'] = 48.0, ['Blue_Minion_Melee'] = 48.0, ['Odin_Blue_Minion_caster'] = 48.0, ['TT_NWraith2'] = 50.0, ['Tutorial_Blue_Minion_Wizard'] = 48.0, ['GiantWolf'] = 65.0, ['Odin_Red_Minion_Caster'] = 48.0, ['Red_Minion_MechMelee'] = 65.0, ['LesserWraith'] = 50.0, ['Red_Minion_Basic'] = 48.0, ['Tutorial_Blue_Minion_Basic'] = 48.0, ['GhostWard'] = 1, ['TT_NWraith'] = 50.0, ['Red_Minion_MechRange'] = 65.0, ['YorickDecayedGhoul'] = 1.0, ['TT_Buffplat_L'] = 0, ['TT_ChaosTurret4'] = 88.4, ['TT_Buffplat_Chain'] = 0, ['TT_OrderTurret4'] = 88.4, ['OrderTurretShrine'] = 88.4, ['ChaosTurretShrine'] = 88.4, ['WriggleLantern'] = 1, ['ChaosTurretTutorial'] = 88.4, ['TwistedLizardElder'] = 65.0, ['RabidWolf'] = 65.0, ['OrderTurretTutorial'] = 88.4, ['OdinShieldRelic'] = 0, ['TwistedGolem'] = 80.0, ['TwistedSmallWolf'] = 50.0, ['TwistedGiantWolf'] = 65.0, ['TwistedTinyWraith'] = 50.0, ['TwistedBlueWraith'] = 50.0, ['TwistedYoungLizard'] = 50.0, ['Summoner_Rider_Order'] = 65.0, ['Summoner_Rider_Chaos'] = 65.0, ['Ghast'] = 60.0, ['blueDragon'] = 100.0, }
    return (hitboxTable[hero.charName] ~= nil and hitboxTable[hero.charName] ~= 0) and hitboxTable[hero.charName] or 65
end


--[[
		_MenuManager Class
]]

class '_MenuManager' MenuManager = nil

function _MenuManager:__init()
	self.AutoCarry = false
	self.MixedMode = false
	self.LastHit = false
	self.LaneClear = false
	self.SkillMenu = {}

	AddTickCallback(function() self:OnTick() end)
	AddMsgCallback(function(msg, key) self:OnWndMsg(msg, key) end)
	AddUnloadCallback(function() self:_OnUnload() end)
	AddBugsplatCallback(function() self:_OnBugsplat() end)
	AddExitCallback(function() self:_OnExit() end)
	MenuManager = self


	--[[ Setup Menu]]
	ModesMenu = scriptConfig("Sida's Auto Carry: Setup", "sidasacsetup")
	ModesMenu:addSubMenu("Auto Carry Mode", "sidasacautocarrysub")
	ModesMenu:addSubMenu("Last Hit Mode", "sidasaclasthitsub")
	ModesMenu:addSubMenu("Mixed Mode", "sidasacmixedmodesub")
	ModesMenu:addSubMenu("Lane Clear Mode", "sidasaclaneclearsub")
	ModesMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	ModesMenu:addParam("TargetLock", "Target Lock", SCRIPT_PARAM_ONKEYDOWN, false, string.byte("G"))
	ModesMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	ModesMenu:addParam("aaDelay", "AA Cancel Fix Amount", SCRIPT_PARAM_SLICE, 0, 0, 300, 0)
	ModesMenu:addParam("aaDelayHelp", "What is this?", SCRIPT_PARAM_ONOFF, false)
	AutoCarryMenu = ModesMenu.sidasacautocarrysub
	LastHitMenu = ModesMenu.sidasaclasthitsub
	MixedModeMenu = ModesMenu.sidasacmixedmodesub
	LaneClearMenu = ModesMenu.sidasaclaneclearsub
	ModesMenu.aaDelayHelp = false

	SkillsMenu = scriptConfig("Sida's Auto Carry: Skills", "sidasacskils")
	for _, Skill in pairs(Skills.SkillsList) do
		SkillsMenu:addSubMenu("Skill "..Skill.DisplayName, Skill.RawName.."sub")
		self.SkillMenu[Skill.Key] = SkillsMenu[Skill.RawName.."sub"]
	end

	ConfigurationMenu = scriptConfig("Sida's Auto Carry: Configuration", "sidasacconfigsub")
	ConfigurationMenu:addSubMenu("Drawing", "sidasacdrawingsub")
	ConfigurationMenu:addSubMenu("Summoner Spells", "sidasacsummonersub")
	ConfigurationMenu:addSubMenu("Extras", "sidasacextrassub")
	ConfigurationMenu:addSubMenu("Melee Config", "sidasacmeleesub")
	ConfigurationMenu:addSubMenu("Farming", "sidasacfarmingsub")
	ConfigurationMenu:addSubMenu("Auto-Update", "sidasacupdatesub")
	ConfigurationMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	ConfigurationMenu:addParam("Focused", "Focus Selected Target", SCRIPT_PARAM_ONOFF, false)
	ConfigurationMenu:addParam("SupportMode", "Support Mode", SCRIPT_PARAM_ONOFF, false)
	ConfigurationMenu:addParam("ToggleRightClickDisable", "Disable Toggle Mode On Right Click", SCRIPT_PARAM_ONOFF, true)
	DrawingMenu = ConfigurationMenu.sidasacdrawingsub
	SummonersMenu = ConfigurationMenu.sidasacsummonersub
	ExtrasMenu = ConfigurationMenu.sidasacextrassub
	MeleeMenu = ConfigurationMenu.sidasacmeleesub
	FarmMenu = ConfigurationMenu.sidasacfarmingsub
	UpdateMenu = ConfigurationMenu.sidasacupdatesub


	--[[ Auto Carry Menu ]]

	--AutoCarryMenu = scriptConfig("Sida's Auto Carry: Auto Carry", "sidasacautocarry")
	AutoCarryMenu:addParam("title", "              Sida's Auto Carry: Reborn", SCRIPT_PARAM_INFO, "")
	AutoCarryMenu:addParam("sep", "-- Settings--", SCRIPT_PARAM_INFO, "")
	AutoCarryMenu:addParam("LeftClick", "Left Click Mode", SCRIPT_PARAM_ONOFF, false)
	AutoCarryMenu:addParam("Toggle", "Toggle mode (requires reload)", SCRIPT_PARAM_ONOFF, false)
	AutoCarryMenu:addParam("Active", "Auto Carry", AutoCarryMenu.Toggle and SCRIPT_PARAM_ONKEYTOGGLE or SCRIPT_PARAM_ONKEYDOWN, false, 32)
	AutoCarryMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	AutoCarryMenu:addParam("sep", "-- Skills --", SCRIPT_PARAM_INFO, "")
	AutoCarryMenu:permaShow("title")
	AutoCarryMenu:permaShow("Active")
	AutoCarryMenu:addTS(Crosshair.Attack_Crosshair)
	Keys:RegisterMenuKey(AutoCarryMenu, "Active", MODE_AUTOCARRY)

	if #Skills.SkillsList > 0 then
		for _, Skill in pairs(Skills.SkillsList) do
			AutoCarryMenu:addParam(Skill.RawName.."AutoCarry", "Use "..Skill.DisplayName, SCRIPT_PARAM_ONOFF, self:LoadSkill(AutoCarryMenu, Skill.RawName, "AutoCarry"))
		end
	else
		AutoCarryMenu:addParam("sep", "No supported skills for "..myHero.charName, SCRIPT_PARAM_INFO, "")
	end

	AutoCarryMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	AutoCarryMenu:addParam("sep", "-- Items --", SCRIPT_PARAM_INFO, "")
	for _, Item in pairs(Items.ItemList) do
		AutoCarryMenu:addParam(Item.RawName.."AutoCarry", "Use "..Item.Name, SCRIPT_PARAM_ONOFF, true)
	end
	AutoCarryMenu:addParam("botrkSave", "Save BotRK for max heal", SCRIPT_PARAM_ONOFF, true)
	AutoCarryMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	AutoCarryMenu:addParam("sep", "-- Moving/Attacking --", SCRIPT_PARAM_INFO, "")
	AutoCarryMenu:addParam("Movement", "Movement Enabled", SCRIPT_PARAM_ONOFF, true)
	AutoCarryMenu:addParam("Attacks", "Attacks Enabled", SCRIPT_PARAM_ONOFF, true)


	--[[ Last Hit Menu ]]

	--LastHitMenu = scriptConfig("Sida's Auto Carry: Last Hit", "sidasaclasthit")
	LastHitMenu:addParam("sep", "-- Settings--", SCRIPT_PARAM_INFO, "")
	LastHitMenu:addParam("Toggle", "Toggle mode (requires reload)", SCRIPT_PARAM_ONOFF, false)
	LastHitMenu:addParam("Active", "Last Hit", LastHitMenu.Toggle and SCRIPT_PARAM_ONKEYTOGGLE or SCRIPT_PARAM_ONKEYDOWN, false, string.byte("X"))
	LastHitMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	LastHitMenu:addParam("sep", "-- Moving/Attacking --", SCRIPT_PARAM_INFO, "")
	LastHitMenu:addParam("Movement", "Movement Enabled", SCRIPT_PARAM_ONOFF, true)
	LastHitMenu:addParam("Attacks", "Attacks Enabled", SCRIPT_PARAM_ONOFF, true)
	LastHitMenu:permaShow("Active")
	Keys:RegisterMenuKey(LastHitMenu, "Active", MODE_LASTHIT)


	--[[ Mixed Mode Menu ]]

	--MixedModeMenu = scriptConfig("Sida's Auto Carry: Mixed Mode", "sidasacmixedmode")
	MixedModeMenu:addParam("sep", "-- Settings--", SCRIPT_PARAM_INFO, "")
	MixedModeMenu:addParam("Toggle", "Toggle mode (requires reload)", SCRIPT_PARAM_ONOFF, false)
	MixedModeMenu:addParam("Active", "Mixed Mode", MixedModeMenu.Toggle and SCRIPT_PARAM_ONKEYTOGGLE or SCRIPT_PARAM_ONKEYDOWN, false, string.byte("C"))
	MixedModeMenu:addParam("MinionPriority", "Prioritise Last Hit Over Harass", SCRIPT_PARAM_ONOFF, true)
	MixedModeMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	MixedModeMenu:addParam("sep", "-- Skills (Against Champions Only) --", SCRIPT_PARAM_INFO, "")
	MixedModeMenu:permaShow("Active")
	Keys:RegisterMenuKey(MixedModeMenu, "Active", MODE_MIXEDMODE)

	if #Skills.SkillsList > 0 then
		for _, Skill in pairs(Skills.SkillsList) do
			MixedModeMenu:addParam(Skill.RawName.."MixedMode", "Use "..Skill.DisplayName, SCRIPT_PARAM_ONOFF, self:LoadSkill(MixedModeMenu, Skill.RawName, "MixedMode"))
		end
	else
		MixedModeMenu:addParam("sep", "No supported skills for "..myHero.charName, SCRIPT_PARAM_INFO, "")
	end

	MixedModeMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	MixedModeMenu:addParam("sep", "-- Items (Against Champions Only) --", SCRIPT_PARAM_INFO, "")
	for _, Item in pairs(Items.ItemList) do
		MixedModeMenu:addParam(Item.RawName.."MixedMode", "Use "..Item.Name, SCRIPT_PARAM_ONOFF, true)
	end
	MixedModeMenu:addParam("botrkSave", "Save BotRK for max heal", SCRIPT_PARAM_ONOFF, true)
	MixedModeMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	MixedModeMenu:addParam("sep", "-- Moving/Attacking --", SCRIPT_PARAM_INFO, "")
	MixedModeMenu:addParam("Movement", "Movement Enabled", SCRIPT_PARAM_ONOFF, true)
	MixedModeMenu:addParam("Attacks", "Attacks Enabled", SCRIPT_PARAM_ONOFF, true)
	

	--[[ Lane Clear Menu ]]

	--LaneClearMenu = scriptConfig("Sida's Auto Carry: Lane Clear", "sidasaclaneclear")
	LaneClearMenu:addParam("sep", "-- Settings--", SCRIPT_PARAM_INFO, "")
	LaneClearMenu:addParam("Toggle", "Toggle mode (requires reload)", SCRIPT_PARAM_ONOFF, false)
	LaneClearMenu:addParam("Active", "Lane Clear", LaneClearMenu.Toggle and SCRIPT_PARAM_ONKEYTOGGLE or SCRIPT_PARAM_ONKEYDOWN, false, string.byte("V"))
	LaneClearMenu:addParam("AttackEnemies", "Attack Enemies", SCRIPT_PARAM_ONOFF, true)
	LaneClearMenu:addParam("MinionPriority", "Prioritise Last Hit Over Harass", SCRIPT_PARAM_ONOFF, true)
	LaneClearMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	LaneClearMenu:addParam("sep", "-- Skills (Against Champions Only) --", SCRIPT_PARAM_INFO, "")
	LaneClearMenu:permaShow("Active")
	Keys:RegisterMenuKey(LaneClearMenu, "Active", MODE_LANECLEAR)

	if #Skills.SkillsList > 0 then
		for _, Skill in pairs(Skills.SkillsList) do
			LaneClearMenu:addParam(Skill.RawName.."LaneClear", "Use "..Skill.DisplayName, SCRIPT_PARAM_ONOFF, self:LoadSkill(LaneClearMenu, Skill.RawName, "LaneClear"))	
		end
	else
		LaneClearMenu:addParam("sep", "No supported skills for "..myHero.charName, SCRIPT_PARAM_INFO, "")
	end

	LaneClearMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	LaneClearMenu:addParam("sep", "-- Items (Against Champions Only) --", SCRIPT_PARAM_INFO, "")
	for _, Item in pairs(Items.ItemList) do
		LaneClearMenu:addParam(Item.RawName.."LaneClear", "Use "..Item.Name, SCRIPT_PARAM_ONOFF, true)
	end
	LaneClearMenu:addParam("botrkSave", "Save BotRK for max heal", SCRIPT_PARAM_ONOFF, true)
	LaneClearMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	LaneClearMenu:addParam("sep", "-- Moving/Attacking --", SCRIPT_PARAM_INFO, "")
	LaneClearMenu:addParam("Movement", "Movement Enabled", SCRIPT_PARAM_ONOFF, true)
	LaneClearMenu:addParam("Attacks", "Attacks Enabled", SCRIPT_PARAM_ONOFF, true)

	--[[ Skills Menus ]]
	for _, Skill in pairs(Skills.SkillsList) do
		--self.SkillMenu[Skill.Key] = scriptConfig("Sida's Auto Carry: Skill "..Skill.DisplayName, Skill.RawName)
		self.SkillMenu[Skill.Key]:addParam("sep", "-- Against Champions --", SCRIPT_PARAM_INFO, "")
		self.SkillMenu[Skill.Key]:addParam("CastPrio", "Cast Priority (0 Is Cast First)", SCRIPT_PARAM_SLICE, 0, 0, 3, 0)
		self.SkillMenu[Skill.Key]:addParam("MinMana", "Mana Must Be Above %", SCRIPT_PARAM_SLICE, 0, 0, 100, 0)
		self.SkillMenu[Skill.Key]:addParam("MinHealth", "Enemy Health Must Be Above %", SCRIPT_PARAM_SLICE, 0, 0, 100, 0)
		self.SkillMenu[Skill.Key]:addParam("MaxHealth", "Enemy Health Must Be Below %", SCRIPT_PARAM_SLICE, 100, 0, 100, 0)
		self.SkillMenu[Skill.Key]:addParam("RequiresTarget", "Target Must Be In Attack Range", SCRIPT_PARAM_ONOFF, Skill.ReqAttackTarget)
		self.SkillMenu[Skill.Key]:addParam("ManualCast", "Manual Cast", SCRIPT_PARAM_ONKEYDOWN, false, 119)
		-- self.SkillMenu[Skill.Key]:addParam("sep", "", SCRIPT_PARAM_INFO, "")
		-- self.SkillMenu[Skill.Key]:addParam("sep", "-- Against Minions --", SCRIPT_PARAM_INFO, "")
		-- self.SkillMenu[Skill.Key]:addParam("LastHit", "Last Hit With This Skill", SCRIPT_PARAM_ONOFF, false)
		-- self.SkillMenu[Skill.Key]:addParam("MinManaMinions", "Minimum Mana %", SCRIPT_PARAM_SLICE, 0, 0, 100, 0)
	end
	
	--[[Configuration Menu ]]
	--ConfigMenu:addParam("multiOptions", "Multi-opts in sub-menu", SCRIPT_PARAM_LIST, 1, { "Default", "Its", "Disco", "Baby" })

	--[[ Extras Menu ]]
	--SummonersMenu = scriptConfig("Sida's Auto Carry: Summoner Spells", "sidasacextras")
	SummonersMenu:addParam("Barrier", "Auto Barrier", SCRIPT_PARAM_ONOFF, true)
	SummonersMenu:addParam("Ignite", "Auto Ignite", SCRIPT_PARAM_ONOFF, true)

	-- ConfigMenu:addParam("Boost", "Turbo Boost: Attack", SCRIPT_PARAM_SLICE, 0, 0, 1000, 0)
	-- ConfigMenu:addParam("AttackBoost", "Turbo Boost: Movement", SCRIPT_PARAM_SLICE, 0, 0, 1000, 0)

	--[[ Drawing Menu ]]
	--DrawingMenu = scriptConfig("Sida's Auto Carry: Drawing", "sidasacdrawing")
	DrawingMenu:addParam("RangeCircle", "Champion Range Circle", SCRIPT_PARAM_ONOFF, true)
	DrawingMenu:addParam("RangeCircleColour", "Colour", SCRIPT_PARAM_COLOR, {255, 0, 189, 22})
	DrawingMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	DrawingMenu:addParam("CastRangeCircle", "Spell Range Circle", SCRIPT_PARAM_ONOFF, true)
	DrawingMenu:addParam("CastRangeCircleColour", "Colour", SCRIPT_PARAM_COLOR, {183, 0, 26, 173})
	DrawingMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	DrawingMenu:addParam("TargetCircle", "Circle Around Target", SCRIPT_PARAM_ONOFF, true)
	DrawingMenu:addParam("TargetCircleColour", "Colour", SCRIPT_PARAM_COLOR, {255, 0, 112, 95})
	DrawingMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	DrawingMenu:addParam("MinionCircle", "Minion Marker With Prediction", SCRIPT_PARAM_ONOFF, true)
	DrawingMenu:addParam("MinionCircleColour", "Colour", SCRIPT_PARAM_COLOR, {183, 0, 26, 173})
	DrawingMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	DrawingMenu:addParam("AlmostMinionCircle", "Draw Almost Killable Minions", SCRIPT_PARAM_ONOFF, true)
	DrawingMenu:addParam("AlmostMinionCircleColour", "Colour", SCRIPT_PARAM_COLOR, {255, 0, 189, 22})
	DrawingMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	DrawingMenu:addParam("ArrowToTarget", "Line To Target", SCRIPT_PARAM_ONOFF, true)
	DrawingMenu:addParam("ArrowToTargetColour", "Colour", SCRIPT_PARAM_COLOR, {100, 255, 100, 100})
	DrawingMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	DrawingMenu:addParam("StandZone", "Stand & Shoot Range", SCRIPT_PARAM_ONOFF, true)
	DrawingMenu:addParam("StandZoneColour", "Stand Zone Colour", SCRIPT_PARAM_COLOR, {183, 0, 26, 173})
	DrawingMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	DrawingMenu:addParam("MeleeSticky", "Stick To Target Range (Melee Only)", SCRIPT_PARAM_ONOFF, true)
	DrawingMenu:addParam("MeleeStickyColour", "Stick To Target Colour", SCRIPT_PARAM_COLOR, {183, 0, 26, 173})
	DrawingMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	DrawingMenu:addParam("TargetLock", "Draw Target Lock Circle", SCRIPT_PARAM_ONOFF, true)
	DrawingMenu:addParam("TargetLockColour", "Target Lock Colour", SCRIPT_PARAM_COLOR, {255, 173, 0, 0})
	DrawingMenu:addParam("TargetLockText", "Target Lock Reminder Text", SCRIPT_PARAM_ONOFF, true)
	DrawingMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	DrawingMenu:addParam("LowFPS", "Use Low FPS Circles", SCRIPT_PARAM_ONOFF, false)

	--[[ Extras Menu ]]
	--ExtrasMenu = scriptConfig("Sida's Auto Carry: Extras", "sidasacextras")
	ExtrasMenu:addParam("sep", "-- May Cause FPS Drops--", SCRIPT_PARAM_INFO, "")
	ExtrasMenu:addParam("ShowSwingTimer", "Show Swing Timer", SCRIPT_PARAM_ONOFF, true)
	ExtrasMenu:addParam("StandZone"..myHero.charName, "Stand Still And Shoot Range", SCRIPT_PARAM_SLICE, self:LoadStandRange(), 0, MyHero.TrueRange, 0)

	FarmMenu:addParam("Butcher", "Butcher Mastery", SCRIPT_PARAM_ONOFF, false)
	FarmMenu:addParam("ArcaneBlade", "Arcane Blade Mastery", SCRIPT_PARAM_ONOFF, false)
	FarmMenu:addParam("Havoc", "Havoc Mastery", SCRIPT_PARAM_ONOFF, true)
	FarmMenu:addParam("DoubleEdgedSword", "Double-Edged Sword Mastery", SCRIPT_PARAM_ONOFF, true)
	FarmMenu:addParam("DevastatingStrikes", "Devastating Strike Mastery", SCRIPT_PARAM_SLICE, 3, 0, 3, 0)
	FarmMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	FarmMenu:addParam("Freeze", "Lane Freeze Toggle", SCRIPT_PARAM_ONKEYTOGGLE, false, 112)
	FarmMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	FarmMenu:addParam("sep", "--- Last Hit Adjustment ---", SCRIPT_PARAM_INFO, "")
	FarmMenu:addParam("FarmOffset", "Last Hit Adjustment", SCRIPT_PARAM_SLICE, 0, -50, 50, 0)
	FarmMenu:addParam("resetOffset", "<Click to reset adjustment>", SCRIPT_PARAM_ONOFF, false)
	FarmMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	FarmMenu:addParam("sep", "--- Lane Clear Speed ---", SCRIPT_PARAM_INFO, "")
	FarmMenu:addParam("LaneClearMultiplier", "No Change", SCRIPT_PARAM_SLICE, 0, 0, 100, 0)
	FarmMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	FarmMenu:addParam("KillWards", "Attack Wards In Lane Clear", SCRIPT_PARAM_ONOFF, false)
	FarmMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	
	local _Skills = Skills:GetLastHitSkills()
	if #_Skills > 0 then
		FarmMenu:addParam("sep", "Use Skills To Secure Last Hits:", SCRIPT_PARAM_INFO, "")
		for _, Skill in pairs(Skills:GetLastHitSkills()) do
			FarmMenu:addParam("FarmSkill"..Skill.RawName, Skill.DisplayName, SCRIPT_PARAM_ONOFF, false)
		end
	end
	
	FarmMenu.FarmOffset = self:LoadFarmOffset()
	FarmMenu.LaneClearMultiplier = self:LoadClearSpeed()

	MeleeMenu:addParam("MeleeStickyRange", "Stick To Target Range (Melee Only)", SCRIPT_PARAM_SLICE, 0, 0, 300, 0)

	UpdateMenu:addParam("Enabled", "Check For Updates On Load", SCRIPT_PARAM_ONOFF, true)

	--[[ Overdrive Menu ]]
	-- OverdriveMenu = scriptConfig("Sida's Auto Carry: Overdrive", "sidasacoverdrive")
	-- OverdriveMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	-- OverdriveMenu:addParam("Enabled", "Enable Overdrive", SCRIPT_PARAM_ONOFF, false)
	-- OverdriveMenu:addParam("sep", "", SCRIPT_PARAM_INFO, "")
	-- OverdriveMenu:addParam("AnimationOverdrive", "Amount", SCRIPT_PARAM_SLICE, 0, 0, 100, 0)
	-- OverdriveMenu:addParam("AnimationOverdriveHelp", "Click for help >>", SCRIPT_PARAM_ONOFF, false)

	self:DisableAllModes()
	-- self.Boost = ConfigMenu.Boost
	-- self.AttackBoost = ConfigMenu.AttackBoost 

	FarmMenu.resetOffset = false
end

function _MenuManager:OnTick()
	-- if self.Boost ~= ConfigMenu.Boost then
	-- 	print("Hyper Charge: Attack - When you start cancelling attacks, you've gone too high!")
	-- 	self.Boost = ConfigMenu.Boost
	-- end

	-- if self.AttackBoost ~= ConfigMenu.AttackBoost then
	-- 	print("Hyper Charge: Movement - When you stand still before each attack, you've gone too high!")
	-- 	self.AttackBoost = ConfigMenu.AttackBoost
	-- end

	if AutoCarryMenu.Active ~= self.AutoCarry and not self.AutoCarry then
		self:SetToggles(true, false, false, false)
	elseif MixedModeMenu.Active ~= self.MixedMode and not self.MixedMode then
		self:SetToggles(false, true, false, false)
	elseif LastHitMenu.Active ~= self.LastHit and not self.LastHit then
		self:SetToggles(false, false, true, false)
	elseif LaneClearMenu.Active ~= self.LaneClear and not self.LaneClear then
		self:SetToggles(false, false, false, true)
	end
	
	if FarmMenu.resetOffset then
		FarmMenu.FarmOffset = 0
		FarmMenu.resetOffset = false
		PrintSystemMessage("Last Hit Adjustment Reset")
	end

	if ModesMenu.aaDelayHelp then
		ModesMenu.aaDelayHelp = false
		PrintSystemMessage("If you have high ping and your auto attacks are cancelling, increase the slider until they no longer cancel! Do not increase unless your attacks are cancelling!")
	end

	if FarmMenu.LaneClearMultiplierHelp then
		FarmMenu.LaneClearMultiplierHelp = false
		PrintSystemMessage("If you find yourself thinking 'SAC you should have waited for that last hit instead of hitting something else in lane clear because now you missed the last hit', increase this slider")
	end

	if FarmMenu.Freeze then
		MixedModeMenu._param[3].text = "Mixed Mode         (Lane Freeze)"
		LastHitMenu._param[3].text = "Last Hit                 (Lane Freeze)"
	else
		MixedModeMenu._param[3].text = "Mixed Mode"
		LastHitMenu._param[3].text = "Last Hit"
	end

	if FarmMenu.FarmOffset < 0 then
		FarmMenu._param[10].text = "You will attack earlier"
	elseif FarmMenu.FarmOffset > 0 then
		FarmMenu._param[10].text = "You will attack later"
	else
		FarmMenu._param[10].text = "No last hit adjustment"
	end

	if FarmMenu.LaneClearMultiplier == 0 then
		FarmMenu._param[14].text = "No Change"
	else
		FarmMenu._param[14].text = "Faster, Less Accurate"
	end
end

function _MenuManager:OnWndMsg(msg, key)
	if msg == WM_RBUTTONDOWN then
		local _Menu = self:GetActiveMenu()
		if _Menu and _Menu.Toggle and ConfigurationMenu.ToggleRightClickDisable then
			self:DisableAllModes()
		end
	end
end

function _MenuManager:_OnUnload()
	self:SaveSkills()
	self:SaveFarmOffset()
	self:SaveClearSpeed()
	self:SaveStandRange()
end

function _MenuManager:_OnBugsplat()
	self:SaveSkills()
	self:SaveFarmOffset()
	self:SaveClearSpeed()
	self:SaveStandRange()
end

function _MenuManager:_OnExit()
	self:SaveSkills()
	self:SaveFarmOffset()
	self:SaveClearSpeed()
	self:SaveStandRange()
end

function _MenuManager:SetToggles(ac, mm, lh, lc)
	AutoCarryMenu.Active, self.AutoCarry = ac, ac
	MixedModeMenu.Active, self.MixedMode = mm, mm
	LastHitMenu.Active, self.LastHit = lh, lh
	LaneClearMenu.Active, self.LaneClear = lc, lc
end

function _MenuManager:DisableAllModes()
	AutoCarryMenu.Active = false
	MixedModeMenu.Active = false
	LastHitMenu.Active = false
	LaneClearMenu.Active = false
end

function _MenuManager:GetActiveMenu()
	if AutoCarryMenu.Active then
		return AutoCarryMenu
	elseif MixedModeMenu.Active then
		return MixedModeMenu
	elseif LastHitMenu.Active then
		return LastHitMenu
	elseif LaneClearMenu.Active then
		return LaneClearMenu
	end
end

function _MenuManager:SaveFarmOffset()
	local save = GetSave("SidasAutoCarry")
	save[myHero.charName.."Offset"] = FarmMenu.FarmOffset
	save:Save()
end

function _MenuManager:LoadFarmOffset()
	local save = GetSave("SidasAutoCarry")[myHero.charName.."Offset"]
	if not save then
		return 0
	else
		return save
	end
end

function _MenuManager:SaveClearSpeed()
	local save = GetSave("SidasAutoCarry")
	save[myHero.charName.."ClearSpeed"] = FarmMenu.LaneClearMultiplier
	save:Save()
end

function _MenuManager:LoadClearSpeed()
	local save = GetSave("SidasAutoCarry")[myHero.charName.."ClearSpeed"]
	if not save then
		return 0
	else
		return save
	end
end

function _MenuManager:SaveSkills()
	local _skills = {}

	for _, Skill in pairs(Skills.SkillsList) do
		if AutoCarryMenu[Skill.RawName.."AutoCarry"] ~= nil then
			table.insert(_skills , {mode = "AutoCarry", name=Skill.RawName, value=AutoCarryMenu[Skill.RawName.."AutoCarry"]})
		end
		if MixedModeMenu[Skill.RawName.."MixedMode"] ~= nil then
			table.insert(_skills , {mode = "MixedMode", name=Skill.RawName, value=MixedModeMenu[Skill.RawName.."MixedMode"]})
		end
		if LaneClearMenu[Skill.RawName.."LaneClear"] ~= nil then
			table.insert(_skills , {mode = "LaneClear", name=Skill.RawName, value=LaneClearMenu[Skill.RawName.."LaneClear"]})
		end
	end

	local save = GetSave("SidasAutoCarry")
	save[myHero.charName] = _skills
	save:Save()
end

function _MenuManager:LoadSkill(Menu, Name, Mode)
	local save = GetSave("SidasAutoCarry")[myHero.charName]
	if not save then return false end
	for _, Skill in pairs(save) do
		if Skill.name == Name and Skill.mode == Mode then
			return Skill.value
		end
	end
	return false
end

function _MenuManager:SaveStandRange()
	local save = GetSave("SidasAutoCarry")

	save.HoldZone = ExtrasMenu["StandZone"..myHero.charName]
	save:Save()
end

function _MenuManager:LoadStandRange()
	local save = GetSave("SidasAutoCarry")

	if save.HoldZone then
		return save.HoldZone
	else
		return 0
	end
end

--[[
		_Plugins Class
]]

class '_Plugins' Plugins = nil

function _Plugins:__init()
	self.Plugins = {}
	self.RegisteredBonusLastHitDamage = {}
	self.RegisteredPreAttack = {}
	Plugins = self
end

function _Plugins:RegisterPlugin(plugin, name)
	if plugin.OnTick then
		AddTickCallback(function() plugin:OnTick() end)
	end
	if plugin.OnDraw then
		AddDrawCallback(function() plugin:OnDraw() end)
	end
	if plugin.OnCreateObj then
		AddCreateObjCallback(function(obj) plugin:OnCreateObj(obj) end)
	end
	if plugin.OnDeleteObj then
		AddDeleteObjCallback(function(obj) plugin:OnDeleteObj(obj) end)
	end
	if plugin.OnLoad then
		plugin:OnLoad()
	end
	if plugin.OnUnload then
		AddUnloadCallback(function() plugin.OnUnload() end)
	end
	if plugin.OnWndMsg then
		AddMsgCallback(function(msg, key) plugin:OnWndMsg(msg, key) end)
	end
	if plugin.OnProcessSpell then
		AddProcessSpellCallback(function(unit, spell) plugin:OnProcessSpell(unit, spell) end)
	end
	if plugin.OnSendChat then
		AddChatCallback(function(text) plugin:OnSendChat(text) end)
	end
	if plugin.OnBugsplat then
		AddBugsplatCallback(function() plugin:OnBugsplat() end) 
	end
	if plugin.OnAnimation then
		AddAnimationCallback(function(unit, anim) plugin:OnAnimation(unit, anim) end)
	end
	if plugin.OnSendPacket then
		AddSendPacketCallback(function(packet) plugin:OnSendPacket(packet) end)
	end
	if plugin.OnRecvPacket then
		AddRecvPacketCallback(function(packet) plugin:OnRecvPacket(packet) end)
	end
	if name then
		self.Plugins[name] = scriptConfig("Sida's Auto Carry Plugin: "..name, "sidasacautocarryplugin"..name)
		return self.Plugins[name]
	end
end

function _Plugins:RegisterBonusLastHitDamage(func)
	table.insert(self.RegisteredBonusLastHitDamage, func)
end

function _Plugins:RegisterPreAttack(func)
	table.insert(self.RegisteredPreAttack, func)
end

function _Plugins:RegisterOnAttacked(func)
	RegisterOnAttacked(func)
end

function _Plugins:GetProdiction(Key, Range, Speed, Delay, Width, Source, Callback)
	return ProdictManager.GetInstance():AddProdictionObject(Key, Range, Speed * 1000, Delay / 1000, Width, myHero, Callback)
end

--[[
		Drawing Class
]]

class '_Drawing'

function _Drawing:__init()
	self.LastTargetLockCircle = 400
	self.LastTargetLockCircleUpdate = GetTickCount()

	AddDrawCallback(function() self:_OnDraw() end)
end

function _Drawing:_OnDraw()

	if DrawingMenu.RangeCircle then
		Helper:DrawCircleObject(myHero, MyHero.TrueRange + 55, Helper:ArgbFromMenu(DrawingMenu.RangeCircleColour))
	end

	if DrawingMenu.CastRangeCircle and Crosshair.Skills_Crosshair.range ~= MyHero.TrueRange then
		Helper:DrawCircleObject(myHero, Crosshair.Skills_Crosshair.range, Helper:ArgbFromMenu(DrawingMenu.CastRangeCircleColour))
	end

	if DrawingMenu.TargetCircle and Crosshair.Target then
		Helper:DrawCircleObject(Crosshair.Target, 100, Helper:ArgbFromMenu(DrawingMenu.TargetCircleColour), 6)
	end

	if DrawingMenu.MinionCircle and Minions.KillableMinion then
		Helper:DrawCircleObject(Minions.KillableMinion, 80, Helper:ArgbFromMenu(DrawingMenu.MinionCircleColour), 6)
	end

	if DrawingMenu.AlmostMinionCircle and Minions.AlmostKillable then
		Helper:DrawCircleObject(Minions.AlmostKillable, 150, Helper:ArgbFromMenu(DrawingMenu.AlmostMinionCircleColour), 4)
	end

	if DrawingMenu.ArrowToTarget and Crosshair.Target then
		self:DrawArrows(myHero, Crosshair.Target)
	end

	if DrawingMenu.StandZone and ExtrasMenu["StandZone"..myHero.charName] > 0 then
		Helper:DrawCircleObject(myHero, ExtrasMenu["StandZone"..myHero.charName], Helper:ArgbFromMenu(DrawingMenu.StandZoneColour))
	end

	if DrawingMenu.MeleeSticky and MeleeMenu.MeleeStickyRange > 0 and myHero.range < 300 then
		Helper:DrawCircleObject(myHero, MeleeMenu.MeleeStickyRange, Helper:ArgbFromMenu(DrawingMenu.MeleeStickyColour))
	end

	if DrawingMenu.TargetLock and Crosshair.TargetLock then
		Helper:DrawCircleObject(Crosshair.TargetLock, self.LastTargetLockCircle, Helper:ArgbFromMenu(DrawingMenu.TargetLockColour), 5)
		if GetTickCount() > self.LastTargetLockCircleUpdate + 30 then
			self.LastTargetLockCircle = (self.LastTargetLockCircle < 400 and self.LastTargetLockCircle + 20 or 50)
			self.LastTargetLockCircleUpdate = GetTickCount()
		end
	end

	if DrawingMenu.TargetLockText and Crosshair.TargetLock then
		DrawText("Target Lock: "..Crosshair.TargetLock.charName, 30, WINDOW_W/2-100, WINDOW_H/2, 0xFFFF0000)
	end
end

function _Drawing:DrawArrows(Start, End)
	if Start and End then
        DrawArrows(D3DXVECTOR3(Start.x, Start.y, Start.z), D3DXVECTOR3(End.x, End.y, End.z), 60, Helper:HexFromMenu(DrawingMenu.ArrowToTargetColour), 100)
    end
end

--[[ 
		_AutoUpdate Class 
]]

class '_AutoUpdate' AutoUpdate = nil

function _AutoUpdate:__init()
	AutoUpdate = self
	self.CurrentVersion = 80
	self.Path = BOL_PATH.."Scripts\\Common\\RebornBetaVersion.beta"
	self.UpdateBoxVisible = false
	self.VersionPath = SCRIPT_PATH .. "/Sida/latest.version"
	self.ConCheckPath = SCRIPT_PATH .. "/Sida/conn.check"
	self.MyFileName = _ENV.FILE_NAME
	self.ScriptPath = SCRIPT_PATH .. self:GetFileName()
	self.NextPrint = nil
	self.DoneCheck = false
	self.DoneLoad = false
	self.LastFileCheck = 0
	self.RequiredFilesDownloadComplete = false
	self.StartedCheckFile = nil
	self.Files, self.Directories = self:GetRequiredFiles()

	if FileExist(self.ConCheckPath) then
		DeleteFile(self.ConCheckPath)
	end
	
	AddLoadCallback(function() self:_OnLoad() end)
	AddDrawCallback(function() self:_OnDraw() end)
	AddMsgCallback(function(Msg, Key) self:_OnWndMsg(Msg, Key) end)
	AddTickCallback(function() self:_OnTick() end)

	AutoUpdate:DownloadRequiredFiles()
end


function _AutoUpdate:GetRequiredDownloads()
	if GetTickCount() > self.LastFileCheck + 5000 then
		if not self:HasRequiredFiles() then
			self.Files, self.Directories = self:GetRequiredFiles()
			self:DownloadRequiredFiles()
			self.LastFileCheck = GetTickCount()
			return false
		else
			self.RequiredFilesDownloadComplete = true
			return true
		end
	else
		return false
	end
end

function _AutoUpdate:GetFileName()
	--if self.MyFileName == "Sida's Auto Carry - Reborn Source.lua" then
		return "Sida's Auto Carry - Reborn.lua"
	--else
	--	return self.MyFileName
	--end
end

function _AutoUpdate:GetCheckFile()
	PrintSystemMessage("Checking update server connectivity...")
	self.StartedCheckFile = GetTickCount()
	DownloadFile("http://autocarryreborn.googlecode.com/svn/Scripts/Common/conn.check", self.ConCheckPath, function() self:ConCheckDownloaded() end)
end

function _AutoUpdate:ConCheckDownloaded()
	if FileExist(self.ConCheckPath) then
		self.CheckFileFound = true
		return true
	end
end

function _AutoUpdate:_OnTick()
	if not self.CheckFileFound then
		if not self.StartedCheckFile then
			self:GetCheckFile()
			return
		elseif GetTickCount() < self.StartedCheckFile + 5000 and not self:ConCheckDownloaded() then
			return
		elseif GetTickCount() > self.StartedCheckFile + 5000 and not self.CancelledDownload then
			self:CannotDownload()
			return
		elseif self.DisableLoad then
			return
		end
	end


	if not self.RequiredFilesDownloadComplete and not self.CancelledDownload then
		self:GetRequiredDownloads()
		return
	elseif not self.DoneLoad and self.CanInit then
		--checkOff()
		RunLoad()
		self.DoneLoad = true
	end

	if not self.DoneCheck and not self.CancelledDownload then
		--if UpdateMenu then
			--if UpdateMenu.Enabled then
				PrintSystemMessage("Checking for updates...")
				self.DoneCheck = true
				self:GetLatestVersion()
			--else
			--	self.DoneCheck = true
			--end
		--end
	end
	if self.NextPrint and GetTickCount() > self.NextPrint then
		self:PrintDownloadedUpdate()
		self.NextPrint = nil
	end

	self:DoSprites()
end

function _AutoUpdate:GetRequiredFiles()
	local Files = {}
	local Directories = {}

	table.insert(Directories, {Name = "SidasAutoCarry", Path = SPRITE_PATH})
	table.insert(Directories, {Name = "SidasAutoCarryPlugins", Path = SCRIPT_PATH})

	-- Sprites
	table.insert(Files, {Name="bar_green.dds", Path = SPRITE_PATH.."SidasAutoCarry/", URL = "http://autocarryreborn.googlecode.com/svn/Sprites/SidasAutoCarry/bar_green.dds", Type = "Sprite", Required = true})
	table.insert(Files, {Name="bar_red.dds", Path = SPRITE_PATH.."SidasAutoCarry/", URL = "http://autocarryreborn.googlecode.com/svn/Sprites/SidasAutoCarry/bar_red.dds", Type = "Sprite", Required = true})
	table.insert(Files, {Name="updater_no.dds", Path = SPRITE_PATH.."SidasAutoCarry/", URL = "http://autocarryreborn.googlecode.com/svn/Sprites/SidasAutoCarry/updater_no.dds", Type = "Sprite"})
	table.insert(Files, {Name="updater_yes.dds", Path = SPRITE_PATH.."SidasAutoCarry/", URL = "http://autocarryreborn.googlecode.com/svn/Sprites/SidasAutoCarry/updater_yes.dds", Type = "Sprite"})
	table.insert(Files, {Name="updater_viewchanges.dds", Path = SPRITE_PATH.."SidasAutoCarry/", URL = "http://autocarryreborn.googlecode.com/svn/Sprites/SidasAutoCarry/updater_viewchanges.dds", Type = "Sprite"})
	table.insert(Files, {Name="updater_unselected.dds", Path = SPRITE_PATH.."SidasAutoCarry/", URL = "http://autocarryreborn.googlecode.com/svn/Sprites/SidasAutoCarry/updater_unselected.dds", Type = "Sprite"})

	-- Libraries
	table.insert(Files, {Name="Prodiction.lua", Path = LIB_PATH, URL = "http://autocarryreborn.googlecode.com/svn/Scripts/Common/Prodiction.lua", Type = "Library", Required = true})
	table.insert(Files, {Name="Collision.lua", Path = LIB_PATH, URL = "http://autocarryreborn.googlecode.com/svn/Scripts/Common/Collision.lua", Type = "Library", Required = true})

	return Files, Directories
end

function _AutoUpdate:CannotDownload()
	PrintSystemMessage("Unable to connect to update server!")
	local required = {}
	local Files, Directories = self:GetRequiredFiles()
	for i, File in pairs(Files) do
		if not FileExist(File.Path..File.Name) and File.Required then
			table.insert(required, File)
		end
	end

	if #required > 0 then
		for i, File in pairs(required) do
			PrintSystemMessage("Please manually download "..File.Name)
			self.DisableLoad = true
		end
	end

	self.CancelledDownload = true

	if not self.DisableLoad then
		self.CanInit = true
	end
end

function _AutoUpdate:DownloadRequiredFiles()
	for i, Directory in pairs(self.Directories) do
		if not DirectoryExist(Directory.Path..Directory.Name) then
			CreateDirectory(Directory.Path..Directory.Name)
			PrintSystemMessage("Created Folder "..Directory.Name)
			table.remove(self.Directories, i)
		end
	end

	for i, File in pairs(self.Files) do
		if not FileExist(File.Path..File.Name) then
			DownloadFile(File.URL, File.Path..File.Name, function() PrintSystemMessage("Downloading "..File.Type.." "..File.Name) end)
			table.remove(self.Files, i)
		end
	end
end

function _AutoUpdate:HasRequiredFiles()
	local Files, Directories = self:GetRequiredFiles()

	for _, File in pairs(Files) do
		if not FileExist(File.Path..File.Name) then
			return false
		end
	end

	for _, Directory in pairs(Directories) do
		if not DirectoryExist(Directory.Path..Directory.Name) then
			return false
		end
	end

	return true
end

function _AutoUpdate:_OnLoad()
	--WriteFile("Revision="..self.CurrentVersion..";", self.Path)
end

function _AutoUpdate:PrintDownloadingUpdate()
	print("")
	print("")
	print("")
	print("<font color='#D859CD'>¸¸.•*¨*••*¨*•.¸¸¸¸.•*¨*••*¨*•.¸¸¸¸.•*¨*••*¨*•.¸¸</font>")
	print("<font color='#adec00'>            Sida's Auto Carry: Reborn</font>")
	print("<font color='#adec00'>                Downloading Update...</font>")
	print("<font color='#adec00'>                   Do not reload!</font>")
	print("<font color='#D859CD'>¸¸.•*¨*••*¨*•.¸¸¸¸.•*¨*••*¨*•.¸¸¸¸.•*¨*••*¨*•.¸¸</font>")
end

function _AutoUpdate:PrintDownloadedUpdate()
	print("")
	print("")
	print("")
	print("<font color='#D859CD'>¸¸.•*¨*••*¨*•.¸¸¸¸.•*¨*••*¨*•.¸¸¸¸.•*¨*••*¨*•.¸¸</font>")
	print("<font color='#adec00'>            Sida's Auto Carry: Reborn</font>")
	print("<font color='#adec00'>                 Updated to v".. self.LatestVersion .."</font>")
	print("<font color='#adec00'>                  Please Reload!</font>")
	print("<font color='#D859CD'>¸¸.•*¨*••*¨*•.¸¸¸¸.•*¨*••*¨*•.¸¸¸¸.•*¨*••*¨*•.¸¸</font>")
end

function _AutoUpdate:_OnDraw()
	if self.UpdateBoxVisible then
		if self.Updater_Unselected then
			self.ActiveSprite:DrawEx(Rect(0, 0, 444, 144), D3DXVECTOR3(0,0,0), D3DXVECTOR3(WINDOW_W / 2 - 222, WINDOW_H / 2 - 72, 0), 255)
		end
	end
end

function _AutoUpdate:DoSprites()
	if self.UpdateBoxVisible then
		if CursorIsUnder(WINDOW_W / 2 - 167, WINDOW_H / 2 - 6, 135, 20) then
			self.ActiveSprite = self.Updater_Yes
		elseif CursorIsUnder(WINDOW_W / 2 - 4, WINDOW_H / 2 - 7, 135, 20) then
			self.ActiveSprite = self.Updater_No
		elseif CursorIsUnder(WINDOW_W / 2 - 88, WINDOW_H / 2 + 32, 135, 20) then
			self.ActiveSprite = self.Updater_Changes
		else
			self.ActiveSprite = self.Updater_Unselected
		end
	end
end

function _AutoUpdate:_OnWndMsg(Msg, Key)
	if self.UpdateBoxVisible then
		if Msg == WM_LBUTTONDOWN then
			if CursorIsUnder(WINDOW_W / 2 - 167, WINDOW_H / 2 - 6, 135, 20) then
				self:DownloadLatestScript()
			elseif CursorIsUnder(WINDOW_W / 2 - 4, WINDOW_H / 2 - 7, 135, 20) then
				self.UpdateBoxVisible = false
				PrintSystemMessage("Update Cancelled.")
				self.CanInit = true
			elseif CursorIsUnder(WINDOW_W / 2 - 88, WINDOW_H / 2 + 32, 135, 20) then
				RunCmdCommand("start /max http://code.google.com/p/autocarryreborn/source/list?path=/Scripts/Sida%27s+Auto+Carry+-+Reborn.lua")
			end
		end
	end
end

function _AutoUpdate:GetLatestVersion()
	DeleteFile(self.VersionPath)
	DelayAction(function() DownloadFile("http://autocarryreborn.googlecode.com/svn/Scripts/Common/Reborn.version", self.VersionPath, function() self:VersionDownloaded() end) end, 0.5)
end

function _AutoUpdate:DownloadLatestScript()
	self:PrintDownloadingUpdate()
	self.UpdateBoxVisible = false
	DeleteFile(self.ScriptPath)
	DelayAction(function() DownloadFile("http://autocarryreborn.googlecode.com/svn/Scripts/Sida's%20Auto%20Carry%20-%20Reborn.lua", self.ScriptPath, function() self:ScriptDownloaded() end) end, 1)
end

function _AutoUpdate:VersionDownloaded()
	local Version = ReadFile(self.VersionPath)
	self.LatestVersion = Version
	if Version then
		if tonumber(Version) > self.CurrentVersion then
			self.Updater_Unselected = GetSprite("SidasAutoCarry\\updater_unselected.dds")
			self.Updater_Yes = GetSprite("SidasAutoCarry\\updater_yes.dds")
			self.Updater_No = GetSprite("SidasAutoCarry\\updater_no.dds")
			self.Updater_Changes = GetSprite("SidasAutoCarry\\updater_viewchanges.dds")
			self.ActiveSprite = self.Updater_Unselected
			self.UpdateBoxVisible = true
			PrintSystemMessage("Version "..Version.." is available (currently on "..self.CurrentVersion..")!")
		else
			PrintSystemMessage("You have the latest version v"..self.CurrentVersion.."!")
			self.CanInit = true
		end
	else
		self.CanInit = true
		PrintSystemMessage("Unable to get latest version number from server!")
	end
end

function _AutoUpdate:ScriptDownloaded()
	self.NextPrint = GetTickCount() + 1000	
end

_AutoUpdate()

--[[
		_Data Class
]]


class '_Data' Data = nil

 function _Data:__init()
	self.ResetSpells = {}
	self.SpellAttacks = {}
	self.NoneAttacks = {}
	self.ChampionData = {}
	self.MinionData = {}
	self.JungleData = {}
	self.ItemData = {}
	self.Skills = {}
	self.EnemyHitBoxes = {}
	self.ImmuneEnemies = {}
	self.WardData = {}
	self.UnitInfo = self:GetUnitData()
	Data = self

	self:__GenerateNoneAttacks()
	self:__GenerateSpellAttacks()
	self:__GenerateResetSpells()
	self:_GenerateMinionData()
	self:_GenerateJungleData()
	self:_GenerateItemData()
	self:__GenerateChampionData()
	self:__GenerateSkillData()
	Data:_GenerateWardData()

	AdvancedCallback:bind("OnGainBuff", function(Unit, Buff) self:OnGainBuff(Unit, Buff) end)
	AdvancedCallback:bind("OnLoseBuff", function(Unit, Buff) self:OnLoseBuff(Unit, Buff) end)

	if GetGameTimer() < self:GetHitBoxLastSavedTime() then
		self:GenerateHitBoxData()
	else
		self:LoadHitBoxData()
	end
 end

 function _Data:OnGainBuff(Unit, Buff)
 	if Unit.team ~= myHero.team and (Buff.name == "UndyingRage" or Buff.name == "JudicatorIntervention") then
 		self.ImmuneEnemies[Unit.charName] = true
 	end
 end

 function _Data:OnLoseBuff(Unit, Buff)
 	if Unit.team ~= myHero.team and (Buff.name == "UndyingRage" or Buff.name == "JudicatorIntervention") then
 		self.ImmuneEnemies[Unit.charName] = nil
 	end
 end

function _Data:__GenerateResetSpells()
	self:AddResetSpell("Powerfist")
	self:AddResetSpell("DariusNoxianTacticsONH")
	self:AddResetSpell("Takedown")
	self:AddResetSpell("Ricochet")
	self:AddResetSpell("BlindingDart")
	self:AddResetSpell("VayneTumble")
	self:AddResetSpell("JaxEmpowerTwo")
	self:AddResetSpell("MordekaiserMaceOfSpades")
	self:AddResetSpell("SiphoningStrikeNew")
	self:AddResetSpell("RengarQ")
	self:AddResetSpell("MonkeyKingDoubleAttack")
	self:AddResetSpell("YorickSpectral")
	self:AddResetSpell("ViE")
	self:AddResetSpell("GarenSlash3")
	self:AddResetSpell("HecarimRamp")
	self:AddResetSpell("XenZhaoComboTarget")
	self:AddResetSpell("LeonaShieldOfDaybreak")
	self:AddResetSpell("ShyvanaDoubleAttack")
	self:AddResetSpell("shyvanadoubleattackdragon")
	self:AddResetSpell("TalonNoxianDiplomacy")
	self:AddResetSpell("TrundleTrollSmash")
	self:AddResetSpell("VolibearQ")
	self:AddResetSpell("PoppyDevastatingBlow")
	self:AddResetSpell("SivirW")
	self:AddResetSpell("Ricochet")
end

function _Data:__GenerateSpellAttacks()
	self:AddSpellAttack("frostarrow")
	self:AddSpellAttack("CaitlynHeadshotMissile")
	self:AddSpellAttack("QuinnWEnhanced")
	self:AddSpellAttack("TrundleQ")
	self:AddSpellAttack("XenZhaoThrust")
	self:AddSpellAttack("XenZhaoThrust2")
	self:AddSpellAttack("XenZhaoThrust3")
	self:AddSpellAttack("GarenSlash2")
	self:AddSpellAttack("RenektonExecute")
	self:AddSpellAttack("RenektonSuperExecute")
	self:AddSpellAttack("KennenMegaProc")
	self:AddSpellAttack("redcardpreattack")
	self:AddSpellAttack("bluecardpreattack")
	self:AddSpellAttack("goldcardpreattack")
	self:AddSpellAttack("MasterYiDoubleStrike")
end

function _Data:__GenerateNoneAttacks()
	self:AddNoneAttack("shyvanadoubleattackdragon")
	self:AddNoneAttack("ShyvanaDoubleAttack")
	self:AddNoneAttack("MonkeyKingDoubleAttack")
end

function _Data:_GenerateMinionData()
	self:AddMinionData((myHero.team == 100 and "Blue" or "Red").."_Minion_Basic", 400, 0)
	self:AddMinionData((myHero.team == 100 and "Blue" or "Red").."_Minion_Caster", 484, 0.65)
	self:AddMinionData((myHero.team == 100 and "Blue" or "Red").."_Minion_Wizard", 484, 0.65)
	self:AddMinionData((myHero.team == 100 and "Blue" or "Red").."_Minion_MechCannon", 365, 1.2)
	self:AddMinionData("obj_AI_Turret", 150, 1.2)
end

function _Data:_GenerateJungleData()
	self:AddJungleMonster("Worm12.1.1", 		1)		-- Baron
	self:AddJungleMonster("Dragon6.1.1", 		1)		-- Dragon
	self:AddJungleMonster("AncientGolem1.1.1", 	1)		-- Blue Buff
	self:AddJungleMonster("AncientGolem7.1.1", 	1)		-- Blue Buff
	self:AddJungleMonster("YoungLizard1.1.2", 	2)		-- Blue Buff Add
	self:AddJungleMonster("YoungLizard7.1.3", 	2)		-- Blue Buff Add
	self:AddJungleMonster("YoungLizard1.1.3", 	2)		-- Blue Buff Add
	self:AddJungleMonster("YoungLizard7.1.2", 	2)		-- Blue Buff Add
	self:AddJungleMonster("LizardElder4.1.1", 	1)		-- Red Buff
	self:AddJungleMonster("LizardElder10.1.1", 	1)		-- Red Buff
	self:AddJungleMonster("YoungLizard4.1.2", 	2)		-- Red Buff Add
	self:AddJungleMonster("YoungLizard4.1.3", 	2)		-- Red Buff Add
	self:AddJungleMonster("YoungLizard10.1.2", 	2)		-- Red Buff Add
	self:AddJungleMonster("YoungLizard10.1.3", 	2)		-- Red Buff Add
	self:AddJungleMonster("GiantWolf2.1.3", 	1)		-- Big Wolf
	self:AddJungleMonster("GiantWolf2.1.1", 	1)		-- Big Wolf
	self:AddJungleMonster("GiantWolf8.1.3", 	1)		-- Big Wolf
	self:AddJungleMonster("GiantWolf8.1.1", 	1)		-- Big Wolf
	self:AddJungleMonster("wolf2.1.1", 			2)		-- Small Wolf
	self:AddJungleMonster("wolf2.1.2", 			2)		-- Small Wolf
	self:AddJungleMonster("wolf8.1.1", 			2)		-- Small Wolf
	self:AddJungleMonster("wolf8.1.2", 			2)		-- Small Wolf
	self:AddJungleMonster("Wolf8.1.3", 			2)		-- Small Wolf
	self:AddJungleMonster("Wolf8.1.2", 			2)		-- Small Wolf
	self:AddJungleMonster("Wolf2.1.3", 			2)		-- Small Wolf
	self:AddJungleMonster("Wolf2.1.2", 			2)		-- Small Wolf
	self:AddJungleMonster("Wraith3.1.3", 		1)		-- Big Wraith
	self:AddJungleMonster("Wraith3.1.1", 		1)		-- Big Wraith
	self:AddJungleMonster("Wraith9.1.3", 		1)		-- Big Wraith
	self:AddJungleMonster("Wraith9.1.1", 		1)		-- Big Wraith
	self:AddJungleMonster("LesserWraith3.1.1", 	2)		-- Small Wraith
	self:AddJungleMonster("LesserWraith3.1.3", 	2)		-- Small Wraith
	self:AddJungleMonster("LesserWraith3.1.2", 	2)		-- Small Wraith
	self:AddJungleMonster("LesserWraith3.1.4", 	2)		-- Small Wraith
	self:AddJungleMonster("LesserWraith9.1.1", 	2)		-- Small Wraith
	self:AddJungleMonster("LesserWraith9.1.2", 	2)		-- Small Wraith
	self:AddJungleMonster("LesserWraith9.1.4", 	2)		-- Small Wraith
	self:AddJungleMonster("LesserWraith9.1.3", 	2)		-- Small Wraith
	self:AddJungleMonster("Golem5.1.2", 		1)		-- Big Golem
	self:AddJungleMonster("Golem11.1.2", 		1)		-- Big Golem
	self:AddJungleMonster("SmallGolem5.1.1", 	2)		-- Small Golem
	self:AddJungleMonster("SmallGolem11.1.1", 	2)		-- Small Golem
	self:AddJungleMonster("GreatWraith13.1.1", 	2)		-- Great Wraith
	self:AddJungleMonster("GreatWraith14.1.1", 	2)		-- Great Wraith
end

function _Data:_GenerateItemData()
	self:AddItemData("Blade of the Ruined King", 	3153, true, 500)
	self:AddItemData("Bilgewater Cutlass", 			3144, true, 500)
	self:AddItemData("Deathfire Grasp", 			3128, true, 750)
	self:AddItemData("Hextech Gunblade", 			3146, true, 400)
	self:AddItemData("Blackfire Torch", 			3188, true, 750)
	self:AddItemData("Frost Queens Claim", 			3098, true, 750)
	self:AddItemData("Talisman of Ascension", 		3098, false)
	self:AddItemData("Ravenous Hydra", 				3074, false)
	self:AddItemData("Sword of the Divine", 		3131, false)
	self:AddItemData("Tiamat", 						3077, false)
	self:AddItemData("Entropy", 					3184, false)
	self:AddItemData("Youmuu's Ghostblade", 		3142, false)
	self:AddItemData("Muramana", 					3042, false)
	self:AddItemData("Randuins Omen", 				3143, false)
end
OBJECT_TYPE_WARD = 0
OBJECT_TYPE_BOX = 1
OBJECT_TYPE_TRAP = 2
function _Data:_GenerateWardData()
					  -- charName	   	 -- Name 			--spellName 	    	-- Type			--Range   --Duration
	self:AddWardData("VisionWard",		"VisionWard", 		"visionward", 		OBJECT_TYPE_WARD, 	 1450,		180000)
	self:AddWardData("SightWard",		"SightWard", 		"sightward", 		OBJECT_TYPE_WARD, 	 1450,		180000)
	self:AddWardData("YellowTrinket",	"SightWard", 		"sightward", 		OBJECT_TYPE_WARD, 	 1450,		180000)
	self:AddWardData("SightWard",		"VisionWard", 		"itemghostward", 	OBJECT_TYPE_WARD, 	 1450,		180000)
	self:AddWardData("SightWard",		"VisionWard", 		"itemminiward", 	OBJECT_TYPE_WARD, 	 1450,		60000)
	self:AddWardData("SightWard",		"SightWard", 		"wrigglelantern", 	OBJECT_TYPE_WARD, 	 1450,		180000)
	self:AddWardData("ShacoBox",		"Jack In The Box", 	"jackinthebox", 	OBJECT_TYPE_BOX, 	 300,		60000)
end

ROLE_AD_CARRY = 1
ROLE_AP = 2
ROLE_SUPPORT = 3
ROLE_BRUISER = 4
ROLE_TANK = 5

function _Data:__GenerateChampionData() 
						-- Champion, Projectile Speed,	GameplayCollisionRadius 	Anti-bug delay 			Role
	self:AddChampionData("Aatrox",			0,					65,						0,				ROLE_BRUISER)
	self:AddChampionData("Ahri",            1.6,   				65,						0,      		ROLE_AP)
	self:AddChampionData("Akali",           0,   				65,						0,      		ROLE_AP)
	self:AddChampionData("Alistar",         0,   				80,						0,      		ROLE_SUPPORT)
	self:AddChampionData("Amumu",           0,   				55,						0,      		ROLE_TANK)
	self:AddChampionData("Anivia",          1.4,	   			65,						0,      		ROLE_AP)
	self:AddChampionData("Annie",           1,   				55,						0,      		ROLE_AP)
	self:AddChampionData("Ashe",            2,   				65,						0,      		ROLE_AD_CARRY)
	self:AddChampionData("Blitzcrank",      0,   				80,						0,      		ROLE_SUPPORT)
	self:AddChampionData("Brand",           1.975,  			65,						0,      		ROLE_AP)
	self:AddChampionData("Caitlyn",         2.5,   				65,						0,      		ROLE_AD_CARRY)
	self:AddChampionData("Cassiopeia",      1.22,   			65,						0,      		ROLE_AP)
	self:AddChampionData("Chogath",         0,   				80,						0,      		ROLE_TANK)
	self:AddChampionData("Corki",           2,   				65,						0,      		ROLE_AD_CARRY)
	self:AddChampionData("Darius",			0,					80,						0,				ROLE_BRUISER)
	self:AddChampionData("Diana",           0,   				65,						0,      		ROLE_AP)
	self:AddChampionData("DrMundo",         0,   				80,						0,      		ROLE_TANK)
	self:AddChampionData("Draven",          1.4,   				65,						0,      		ROLE_AD_CARRY)
	self:AddChampionData("Elise",			0,					65,						0,				ROLE_BRUISER)
	self:AddChampionData("Evelynn",         0,   				65,						0,      		ROLE_AP)
	self:AddChampionData("Ezreal",          2,   				65,						0,      		ROLE_AD_CARRY)
	self:AddChampionData("FiddleSticks",    1.75,   			65,						0,      		ROLE_AP)
	self:AddChampionData("Fiora",			0,					65,						0,				ROLE_BRUISER)
	self:AddChampionData("Fizz",            0,   				65,						0,      		ROLE_AP)
	self:AddChampionData("Galio",           0,   				80,						0,      		ROLE_TANK)
	self:AddChampionData("Gangplank",		0,					65,						0,				ROLE_BRUISER)
	self:AddChampionData("Garen",			0,					65,						0,				ROLE_BRUISER)
	self:AddChampionData("Gragas",          0,   				80,						0,      		ROLE_AP)
	self:AddChampionData("Graves",          3,   				65,						0,      		ROLE_AD_CARRY)
	self:AddChampionData("Hecarim",         0,   				80,						0,      		ROLE_TANK)
	self:AddChampionData("Heimerdinger",    1.4,   				55,						0,      		ROLE_AP)
	self:AddChampionData("Irelia",			0,					65,						0,				ROLE_BRUISER)
	self:AddChampionData("Janna",           1.2,   				65,						0,      		ROLE_SUPPORT)
	self:AddChampionData("JarvanIV",		0,					65,						0,				ROLE_BRUISER)
	self:AddChampionData("Jax",				0,					65,						0,				ROLE_BRUISER)
	self:AddChampionData("Jayce",           2.2,   				65,						0,      		ROLE_AD_CARRY)
	self:AddChampionData("Jinx",           	2,   				65,						0,      		ROLE_AD_CARRY)
	self:AddChampionData("Karma",           1.2,   				65,						0,      		ROLE_SUPPORT)
	self:AddChampionData("Karthus",         1.25,   			65,						0,      		ROLE_AP)
	self:AddChampionData("Kassadin",        0,   				65,						0,      		ROLE_AP)
	self:AddChampionData("Katarina",        0,   				65,						0,      		ROLE_AP)
	self:AddChampionData("Kayle",           1.8,   				65,						0,      		ROLE_AP)
	self:AddChampionData("Kennen",          1.35,   			55,						0,      		ROLE_AP)
	self:AddChampionData("Khazix",			0,					65,						0,				ROLE_BRUISER)
	self:AddChampionData("KogMaw",          1.8,   				65,						0,      		ROLE_AD_CARRY)
	self:AddChampionData("Leblanc",         1.7,   				65,						0,      		ROLE_AP)
	self:AddChampionData("LeeSin",			0,					65,						0,				ROLE_BRUISER)
	self:AddChampionData("Leona",           0,   				65,						0,      		ROLE_SUPPORT)
	self:AddChampionData("Lissandra",       0,   				65,						0,      		ROLE_AP)
	self:AddChampionData("Lucian",          2,   				65,						0,      		ROLE_AD_CARRY)
	self:AddChampionData("Lulu",            2.5,   				65,						0,      		ROLE_SUPPORT)
	self:AddChampionData("Lux",            	1.55,   			65,						0,      		ROLE_AP)
	self:AddChampionData("Malphite",        0,   				80,						0,      		ROLE_TANK)
	self:AddChampionData("Malzahar",        1.5,   				65,						0,      		ROLE_AP)
	self:AddChampionData("Maokai",          0,   				80,						0,      		ROLE_TANK)
	self:AddChampionData("MasterYi",        0,   				65,						0,      		ROLE_AP)
	self:AddChampionData("MissFortune",     2,   				65,						0,      		ROLE_AD_CARRY)
	self:AddChampionData("MonkeyKing",		0,					65,						0,				ROLE_BRUISER)
	self:AddChampionData("Mordekaiser",     0,   				80,						0,      		ROLE_AP)
	self:AddChampionData("Morgana",         1.6,   				65,						0,      		ROLE_AP)
	self:AddChampionData("Nami",            0,   				65,						0,      		ROLE_SUPPORT)
	self:AddChampionData("Nasus",           0,   				80,						0,      		ROLE_TANK)
	self:AddChampionData("Nautilus",		0,					80,						0,				ROLE_BRUISER)
	self:AddChampionData("Nidalee",         1.7,   				65,						0,      		ROLE_AP)
	self:AddChampionData("Nocturne",		0,					65,						0,				ROLE_BRUISER)
	self:AddChampionData("Nunu",            0,   				65,						0,      		ROLE_SUPPORT)
	self:AddChampionData("Olaf",			0,					65,						0,				ROLE_BRUISER)
	self:AddChampionData("Orianna",         1.4,   				65,						0,      		ROLE_AP)
	self:AddChampionData("Pantheon",        0,   				65,						0,      		ROLE_AD_CARRY)
	self:AddChampionData("Poppy",			0,					55,						0,				ROLE_BRUISER)
	self:AddChampionData("Quinn",           1.85,   			65,						0,      		ROLE_AD_CARRY)
	self:AddChampionData("Rammus",          0,   				65,						0,      		ROLE_TANK)
	self:AddChampionData("Renekton",		0,					80,						0,				ROLE_BRUISER)
	self:AddChampionData("Rengar",			0,					65,						0,				ROLE_BRUISER)
	self:AddChampionData("Riven",			0,					65,						0,				ROLE_BRUISER)
	self:AddChampionData("Rumble",          0,   				80,						0,      		ROLE_AP)
	self:AddChampionData("Ryze",            2.4,   				65,						0,      		ROLE_AP)
	self:AddChampionData("Sejuani",         0,   				80,						0,      		ROLE_TANK)
	self:AddChampionData("Shaco",           0,   				65,						0,      		ROLE_AD_CARRY)
	self:AddChampionData("Shen",            0,   				65,						0,      		ROLE_TANK)
	self:AddChampionData("Shyvana",			0,					50,						0,				ROLE_BRUISER)
	self:AddChampionData("Singed",          0,   				65,						0,      		ROLE_TANK)
	self:AddChampionData("Sion",            0,   				80,						0,      		ROLE_AP)
	self:AddChampionData("Sivir",           1.4,   				65,						0,      		ROLE_AD_CARRY)
	self:AddChampionData("Skarner",         0,   				80,						0,      		ROLE_TANK)
	self:AddChampionData("Sona",            1.6,   				65,						0,      		ROLE_SUPPORT)
	self:AddChampionData("Soraka",          1,   				65,						0,      		ROLE_SUPPORT)
	self:AddChampionData("Swain",           1.6,   				65,						0,      		ROLE_AP)
	self:AddChampionData("Syndra",          1.2,   				65,						0,      		ROLE_AP)
	self:AddChampionData("Talon",           0,   				65,						0,      		ROLE_AD_CARRY)
	self:AddChampionData("Taric",           0,   				65,						0,      		ROLE_SUPPORT)
	self:AddChampionData("Teemo",           1.3,   				55,						0,      		ROLE_AP)
	self:AddChampionData("Thresh",          0,   				55,						0,      		ROLE_SUPPORT)
	self:AddChampionData("Tristana",        2.25,   			55,						0,      		ROLE_AD_CARRY)
	self:AddChampionData("Trundle",			0,					65,						0,				ROLE_BRUISER)
	self:AddChampionData("Tryndamere",		0,					65,						0,				ROLE_BRUISER)
	self:AddChampionData("TwistedFate",     1.5,   				65,						0,      		ROLE_AP)
	self:AddChampionData("Twitch",          2.5,   				65,						0,      		ROLE_AD_CARRY)
	self:AddChampionData("Udyr",			0,					65,						0,				ROLE_BRUISER)
	self:AddChampionData("Urgot",           1.3,   				80,						0,      		ROLE_AD_CARRY)
	self:AddChampionData("Varus",           2,   				65,						0,      		ROLE_AD_CARRY)
	self:AddChampionData("Vayne",           2,   				65,						0,     			ROLE_AD_CARRY)
	self:AddChampionData("Veigar",          1.05,   			55,						0,      		ROLE_AP)
	self:AddChampionData("Velkoz",			1.8,				55,						0,				ROLE_AP)
	self:AddChampionData("Vi",				0,					50,						0,				ROLE_BRUISER)
	self:AddChampionData("Viktor",          2.25,   			65,						0,      		ROLE_AP)
	self:AddChampionData("Vladimir",        1.4,   				65,						0,      		ROLE_AP)
	self:AddChampionData("Volibear",        0,   				80,						0,      		ROLE_TANK)
	self:AddChampionData("Warwick",         0,   				65,						0,      		ROLE_TANK)
	self:AddChampionData("Xerath",          1.2,   				65,						0,      		ROLE_AP)
	self:AddChampionData("XinZhao",			0,					65,						0,				ROLE_BRUISER)
	self:AddChampionData("Yasuo",          	0,   				65,						0,      		ROLE_BRUISER)
	self:AddChampionData("Yorick",          0,   				80,						0,      		ROLE_TANK)
	self:AddChampionData("Zac",             0,   				65,						0,      		ROLE_TANK)
	self:AddChampionData("Zed",             0,   				65,						0,      		ROLE_AD_CARRY)
	self:AddChampionData("Ziggs",           1.5,   				55,						0,      		ROLE_AP)
	self:AddChampionData("Zilean",          1.25,   			65,						0,      		ROLE_SUPPORT)
	self:AddChampionData("Zyra",            1.7,   				65,						0,      		ROLE_AP)
end							
							
function _Data:__GenerateSkillData()
				  --Name 			  Enabled    Key 	 Range 		Display Name 				Type 			MinMana  AfterAA Require Attack Target 	   Speed 		Delay 	Width 	Collision  	 ResetAA
	self:AddSkillData("Ezreal",		 	true,	 _Q,	 1100,	 "Q (Mystic Shot)",	 		SPELL_LINEAR_COL,		0,	 false,	 	false,	 				2,	 		250,	70,	 	true, 		 false)
	self:AddSkillData("Ezreal",		 	true,	 _W,	 1050,	 "W (Essence Flux)",		SPELL_LINEAR,	 		0,	 false,	 	false,	 				1.6,	 	250,	90,	 	false, 		 false)
	self:AddSkillData("KogMaw",		 	true,	 _Q,	 625,	 "Q (Caustic Spittle)",	 	SPELL_TARGETED,	 		0,	 true,	 	true,	 				1.3,	 	260,	200,	false, 		 false)
	self:AddSkillData("KogMaw",		 	true,	 _W,	 625,	 "W (Bio-Arcane Barrage)",	SPELL_SELF,	 			0,	 false,	 	false,	 				1.3,	 	260,	200,	false, 		 false)
	self:AddSkillData("KogMaw",		 	true,	 _E,	 850,	 "E (Void Ooze)",	 		SPELL_LINEAR,	 		0,	 false,	 	false,	 				1.3,	 	260,	200,	false, 		 false)
	self:AddSkillData("KogMaw",		 	true,	 _R,	 1700,	 "R (Living Artillery)",	SPELL_LINEAR,	 		0,	 false,	 	false,	 				math.huge,	1000,	200,	false, 		 false)
	self:AddSkillData("Sivir",		 	true,	 _Q,	 1000,	 "Q (Boomerang Blade)",	 	SPELL_LINEAR,	 		0,	 false,	 	false,	 				1.33,	 	250,	120,	false, 		 false)
	self:AddSkillData("Sivir",		 	true,	 _W,	 900,	 "W (Ricochet)",	 		SPELL_SELF,	 			0,	 true,	 	true,	 				1,	 		0,	 	200,	false, 		 true)
	self:AddSkillData("Graves",		 	true,	 _Q,	 750,	 "Q (Buck Shot)",	 		SPELL_CONE,	 			0,	 false,	 	false,	 				2,	 		250,	200,	false, 		 false)
	self:AddSkillData("Graves",		 	true,	 _W,	 700,	 "W (Smoke Screen)",	 	SPELL_CIRCLE,	 		0,	 false,	 	false,	 				1400,	 	300,	500,	false, 		 false)
	self:AddSkillData("Graves",		 	true,	 _E,	 580,	 "E (Quick Draw)",	 		SPELL_SELF_AT_MOUSE,	0,	 true,	 	true,	 				1450,	 	250,	200,	false, 		 false)
	self:AddSkillData("Caitlyn",		true,	 _Q,	 1300,	 "Q (Piltover Peacemaker)",	SPELL_LINEAR,			0,	 false,	 	false,	 				2.1,	 	625,	100,	true, 		 false)
	self:AddSkillData("Corki",		 	true,	 _Q,	 600,	 "Q (Phosphorus Bomb)",	 	SPELL_CIRCLE,			0,	 false,	 	false,	 				2,	 		200,	500,	false, 		 false)
	self:AddSkillData("Corki",		 	true,	 _R,	 1225,	 "R (Missile Barrage)",	 	SPELL_LINEAR_COL,		0,	 false,	 	false,	 				2,	 		200,	50,	 	true, 		 false)
	self:AddSkillData("Teemo",		 	true,	 _Q,	 580,	 "Q (Blinding Dart)",	 	SPELL_TARGETED,	 		0,	 false,	 	false,	 				2,	 		0,		200,	false, 		 true)
	self:AddSkillData("TwistedFate",	true,	 _Q,	 1200,	 "Q (Wild Cards)",	 		SPELL_LINEAR,	 		0,	 false,	 	false,	 				1.45,		250,	200,	false, 		 false)
	self:AddSkillData("Vayne",			true,	 _Q,	 750,	 "Q (Tumble)",	 			SPELL_SELF_AT_MOUSE,	0,	 true,	 	true,	 				1.45,		250,	200,	false, 		 true)
	self:AddSkillData("Vayne",			true,	 _R,	 580,	 "R (Final Hour)",	 		SPELL_SELF,	 			0,	 false,	 	true,	 				1.45,		250,	200,	false, 		 false)
	self:AddSkillData("MissFortune",	true,	 _Q,	 650,	 "Q (Double Up)",	 		SPELL_TARGETED,	 		0,	 true,	 	true,	 				1.45,		250,	200,	false, 		 false)
	self:AddSkillData("MissFortune",	true,	 _W,	 580,	 "W (Impure Shots)",	 	SPELL_SELF,	 			0,	 false,	 	true,	 				1.45,		250,	200,	false, 		 false)
	self:AddSkillData("MissFortune",	true,	 _E,	 800,	 "E (Make It Rain)",	 	SPELL_CIRCLE,	 		0,	 false,	 	false,	 				math.huge,	500,	500,	false, 		 false)
	self:AddSkillData("Tristana",		true,	 _Q,	 580,	 "Q (Rapid Fire)",	 		SPELL_SELF,	 			0,	 false,	 	true,	 				1.45,	 	250,	200,	false, 		 false)
	self:AddSkillData("Tristana",		true,	 _E,	 550,	 "E (Explosive Shot)",		SPELL_TARGETED,			0,	 true,	 	false,	 				1.45,	 	250,	200,	false, 		 false)
	self:AddSkillData("Draven",			true,	 _E,	 950,	 "E (Stand Aside)",	 		SPELL_LINEAR,			0,	 false,	 	false,	 				1.37,	 	300,	130,	false, 		 false)
	self:AddSkillData("Kennen",			true,	 _Q,	 1050,	 "Q (Thundering Shuriken)",	SPELL_LINEAR_COL,		0,	 false,	 	false,	 				1.65,	 	180,	80,	 	true, 		 false)
	self:AddSkillData("Ashe",			true,	 _W,	 1200,	 "W (Volley)",	 			SPELL_LINEAR_COL,		0,	 false,	 	false,	 				2,	 		120,	85,	 	true, 		 false)
	self:AddSkillData("Syndra",			true,	 _Q,	 800,	 "Q (Dark Sphere)",	 		SPELL_CIRCLE,	 		0,	 false,	 	false,	 				math.huge,	400,	100,	false, 		 false)
	self:AddSkillData("Jayce",			true,	 _Q,	 1600,	 "Q (Shock Blast)",	 		SPELL_LINEAR_COL,		0,	 false,	 	false,	 				2,	 		350,	90,	 	true, 		 false)
	self:AddSkillData("Nidalee",		true,	 _Q,	 1500,	 "Q (Javelin Toss)",	 	SPELL_LINEAR_COL,		0,	 false,	 	false,	 				1.3,		125,	80,	 	true, 		 false)
	self:AddSkillData("Varus",			true,	 _E,	 925,	 "E (Hail of Arrows)",	 	SPELL_CIRCLE,	 		0,	 false,	 	false,	 				1.75,		240,	235,	false, 		 false)
	self:AddSkillData("Quinn",			true,	 _Q,	 1050,	 "Q (Blinding Assault)",	SPELL_LINEAR_COL,		0,	 false,	 	false,	 				1.55,		220,	90,	 	true, 		 false)
	self:AddSkillData("LeeSin",			true,	 _Q,	 975,	 "Q (Sonic Wave)",	 		SPELL_LINEAR_COL,		0,	 false,	 	false,	 				1.5,		250,	70,	 	true, 		 false)
	self:AddSkillData("Twitch",		 	true,	 _W,	 950,	 "W (Venom Cask)",	 		SPELL_CIRCLE,	 		0,	 false,	 	false,	 				1.4,		250,	275,	false, 		 false)
	self:AddSkillData("Darius",		 	true,	 _W,	 300,	 "W (Crippling Strike)",	SPELL_SELF,	 			0,	 true,	 	true,	 				2,	 		0,	 	200,	true, 		 true)
	self:AddSkillData("Hecarim",		true,	 _Q,	 300,	 "Q (Rampage)",	 			SPELL_SELF,	 			0,	 true,	 	true,	 				2,	 		0,	 	200,	true, 		 false)
	self:AddSkillData("Warwick",		true,	 _Q,	 300,	 "Q (Hungering Strike)",	SPELL_TARGETED,	 		0,	 true,	 	true,	 				2,	 		0,	 	200,	true, 		 false)
	self:AddSkillData("MonkeyKing",		true,	 _Q,	 300,	 "Q (Crushing Blow)",		SPELL_SELF,	 			0,	 true,	 	true,	 				2,	 		0,	 	200,	true, 		 true)
	self:AddSkillData("Poppy",		 	true,	 _Q,	 300,	 "Q (Devastating Blow)",	SPELL_SELF,	 			0,	 true,	 	true,	 				2,	 		0,	 	200,	true, 		 true)
	self:AddSkillData("Talon",		 	true,	 _Q,	 300,	 "Q (Noxian Diplomacy)",	SPELL_SELF,	 			0,	 true,	 	true,	 				2,	 		0,	 	200,	true, 		 false)
	self:AddSkillData("Nautilus",		true,	 _W,	 300,	 "W (Titans Wrath)",	 	SPELL_SELF,	 			0,	 true,	 	true,	 				2,	 		0,	 	200,	true, 		 false)
	self:AddSkillData("Vi",		 		true,	 _E,	 300,	 "E (Excessive Force)",	 	SPELL_SELF,	 			0,	 true,	 	true,	 				2,	 		0,	 	200,	true, 		 true)
	self:AddSkillData("Rengar",		 	true,	 _Q,	 300,	 "Q (Savagery)",	 		SPELL_SELF,	 			0,	 true,	 	true,	 				2,	 		0,	 	200,	true, 		 false)
	self:AddSkillData("Trundle",		true,	 _Q,	 300,	 "Q (Chomp)",	 			SPELL_SELF,	 			0,	 true,	 	true,	 				2,	 		0,	 	200,	true, 		 true)
	self:AddSkillData("Leona",		 	true,	 _Q,	 300,	 "Q (Shield Of Daybreak)",	SPELL_SELF,	 			0,	 true,	 	true,	 				2,	 		0,	 	200,	true, 		 false)
	self:AddSkillData("Fiora",		 	true,	 _E,	 300,	 "E (Burst Of Speed)",	 	SPELL_SELF,	 			0,	 true,	 	true,	 				2,	 		0,	 	200,	true, 		 true)
	self:AddSkillData("Blitzcrank",		true,	 _E,	 300,	 "E (Power Fist)",	 		SPELL_SELF,	 			0,	 true,	 	true,	 				2,	 		0,	 	200,	true, 		 true)
	self:AddSkillData("Shyvana",		true,	 _Q,	 300,	 "Q (Twin Blade)",	 		SPELL_SELF,	 			0,	 true,	 	true,	 				2,	 		0,	 	200,	true, 		 false)
	self:AddSkillData("Renekton",		true,	 _W,	 300,	 "W (Ruthless Predator)",	SPELL_SELF,	 			0,	 true,	 	true,	 				2,	 		0,	 	200,	true, 		 true)
	self:AddSkillData("Jax",		 	true,	 _W,	 300,	 "W (Empower)",	 			SPELL_SELF,	 			0,	 true,	 	true,	 				2,	 		0,	 	200,	true, 		 true)
	self:AddSkillData("XinZhao",		true,	 _Q,	 300,	 "Q (Three Talon Strike)",	SPELL_SELF,	 			0,	 true,	 	true,	 				2,	 		0,	 	200,	true, 		 true)
	self:AddSkillData("Nunu",			true,	 _E,	 300,	 "E (Snowball)",	 		SPELL_TARGETED,			0,	 false,	 	false,	 				1.45,		250,	200,	false, 		 false)
	self:AddSkillData("Khazix",			true,	 _Q,	 300,	 "Q (Taste Their Fear)",	SPELL_TARGETED,			0,	 true,	 	true,	 				1.45,		250,	200,	false, 		 false)
	self:AddSkillData("Shen",			true,	 _Q,	 300,	 "Q (Vorpal Blade)",	 	SPELL_TARGETED,			0,	 false,	 	false,	 				1.45,		250,	200,	false, 		 false)
	self:AddSkillData("Gangplank",		true,	 _Q,	 625,	 "Q (Parrrley)",	 		SPELL_TARGETED,			0,	 true,	 	true,	 				1.45,		0,		200,	false, 		 false)
	self:AddSkillData("Garen",			true,	 _Q,	 300,	 "Q (Decisive Strike)",	 	SPELL_SELF,				0,	 true,	 	false,	 				1.45,		0,		200,	false, 		 true)
	self:AddSkillData("Jayce",			true,	 _W,	 300,	 "W (Hyper Charge)",	 	SPELL_SELF,				0,	 true,	 	false,	 				1.45,		0,		200,	false, 		 true)
	self:AddSkillData("Leona",			true,	 _Q,	 300,	 "Q (Shield of Daybreak)",	SPELL_SELF,				0,	 true,	 	false,	 				1.45,		0,		200,	false, 		 true)
	self:AddSkillData("Mordekaiser",	true,	 _Q,	 300,	 "Q (Mace of Spades)",		SPELL_SELF,				0,	 true,	 	false,	 				1.45,		0,		200,	false, 		 true)
	self:AddSkillData("Nasus",			true,	 _Q,	 300,	 "Q (Siphoning Strike)",	SPELL_SELF,				0,	 true,	 	false,	 				1.45,		0,		200,	false, 		 true)
	self:AddSkillData("Nautilus",		true,	 _W,	 300,	 "W (Titan's Wrath)",		SPELL_SELF,				0,	 true,	 	false,	 				1.45,		0,		200,	false, 		 true)
	self:AddSkillData("Nidalee",		true,	 _Q,	 300,	 "Q (Takedown)",		    SPELL_SELF,				0,	 true,	 	false,	 				1.45,		0,		200,	false, 		 true)
	self:AddSkillData("Rengar",			true,	 _Q,	 300,	 "Q (Savagery)",		    SPELL_SELF,				0,	 true,	 	false,	 				1.45,		0,		200,	false, 		 true)
	self:AddSkillData("Rengar",			true,	 _Q,	 300,	 "Q (Empowered Savagery)",	SPELL_SELF,				0,	 true,	 	false,	 				1.45,		0,		200,	false, 		 true)
	self:AddSkillData("Shyvana",		true,	 _Q,	 300,	 "Q (Twin Bite)",			SPELL_SELF,				0,	 true,	 	false,	 				1.45,		0,		200,	false, 		 true)
	self:AddSkillData("Talon",			true,	 _Q,	 300,	 "Q (Noxian Diplomacy)",	SPELL_SELF,				0,	 true,	 	false,	 				1.45,		0,		200,	false, 		 true)
	self:AddSkillData("Volibear",		true,	 _Q,	 300,	 "Q (Rolling Thunder",		SPELL_SELF,				0,	 true,	 	false,	 				1.45,		0,		200,	false, 		 true)
	self:AddSkillData("Yorick",			true,	 _Q,	 300,	 "Q (Omen of War",			SPELL_SELF,				0,	 true,	 	false,	 				1.45,		0,		200,	false, 		 true)
end

function _Data:GetUnitData()
	local unitInfo = {}
	unitInfo["Blue_Minion_Basic"] = 		{aaDelay = 333, projSpeed = 0, delayOffset = 200, isMinion = true}
	unitInfo["Blue_Minion_Wizard"] =     { aaDelay = 460, projSpeed = 0.68, delayOffset = 150, distOffset = 80, isMinion = true, projectileName = "Mfx_bcm_mis.troy" }
	unitInfo["Odin_Blue_Minion_Caster"] =     { aaDelay = 460, projSpeed = 0.68, delayOffset = 150, distOffset = 80, isMinion = true, projectileName = "Mfx_pcm_mis.troy" }
	unitInfo["Blue_Minion_MechCannon"] = { aaDelay = 365, projSpeed = 1.18, delayOffset = 150, isSiegeMinion = true, isMinion = true, projectileName = "SpikeBullet.troy" }
	unitInfo["OdinBlueSuperminion"] = { aaDelay = 365, projSpeed = 1.18, delayOffset = 150, isSiegeMinion = true, isMinion = true, projectileName = "SpikeBullet.troy" }
	unitInfo["Blue_Minion_MechMelee"] = { projSpeed = 0, delayOffset = 100, isSiegeMinion = true, isMinion = true }
	
	unitInfo["Red_Minion_Basic"] = 		{aaDelay = 333, projSpeed = 0, delayOffset = 200, isMinion = true}
	unitInfo["Red_Minion_Wizard"] =     { aaDelay = 460, projSpeed = 0.68, delayOffset = 150, distOffset = 80, isMinion = true, projectileName = "Mfx_pcm_mis.troy" }
	unitInfo["Odin_Red_Minion_Caster"] =     { aaDelay = 460, projSpeed = 0.68, delayOffset = 150, distOffset = 80, isMinion = true, projectileName = "Mfx_pcm_mis.troy" }
	unitInfo["Red_Minion_MechCannon"] = { aaDelay = 365, projSpeed = 1.18, delayOffset = 150, isSiegeMinion = true, isMinion = true, projectileName = "TristannaBasicAttack_mis.troy" }
	unitInfo["OdinRedSuperminion"] = { aaDelay = 365, projSpeed = 1.18, delayOffset = 150, isSiegeMinion = true, isMinion = true, projectileName = "TristannaBasicAttack_mis.troy" }
	unitInfo["Red_Minion_MechMelee"] = { projSpeed = 0, delayOffset = 100, isSiegeMinion = true, isMinion = true }

	-- Blue Turrets
	unitInfo["OrderTurretNormal"] =	{ aaDelay = 150, projSpeed = 1.14, yOffset = 400, delayOffset = 50, isTurret = true, projectileName = "OrderTurretFire2_mis.troy" }
	unitInfo["OrderTurretNormal2"] =	{ aaDelay = 150, projSpeed = 1.14, yOffset = 400, delayOffset = 50, isTurret = true, projectileName = "OrderTurretFire2_mis.troy"  }
	unitInfo["OrderTurretDragon"] =	{ aaDelay = 150, projSpeed = 1.14, yOffset = 400, delayOffset = 50, isTurret = true, projectileName = "OrderTurretFire2_mis.troy" }
	unitInfo["OrderTurretAngel"] =	{ aaDelay = 150, projSpeed = 1.14, yOffset = 400, delayOffset = 50, isTurret = true, projectileName = "OrderTurretFire2_mis.troy" }
	
	-- Red Turrets
	unitInfo["ChaosTurretWorm"] =	{ aaDelay = 150, projSpeed = 1.14, yOffset = 400, delayOffset = 50, isTurret = true, projectileName = "ChaosTurretFire2_mis.troy" }
	unitInfo["ChaosTurretWorm2"] =	{ aaDelay = 150, projSpeed = 1.14, yOffset = 400, delayOffset = 50, isTurret = true, projectileName = "ChaosTurretFire2_mis.troy"  }
	unitInfo["ChaosTurretGiant"] =	{ aaDelay = 150, projSpeed = 1.14, yOffset = 400, delayOffset = 50, isTurret = true, projectileName = "ChaosTurretFire2_mis.troy"  }
	unitInfo["ChaosTurretNormal"] =	{ aaDelay = 150, projSpeed = 1.14, yOffset = 400, delayOffset = 50, isTurret = true, projectileName = "ChaosTurretFire2_mis.troy"  }

	return unitInfo
end

function _Data:IsMinion(Minion)
	return self:GetUnitData()[Minion.charName]
end

function _Data:AddResetSpell(name)
	self.ResetSpells[name] = true
end

function _Data:AddSpellAttack(name)
	self.SpellAttacks[name] = true
end

function _Data:AddNoneAttack(name)
	self.NoneAttacks[name] = true
end

function _Data:AddChampionData(Champion, ProjSpeed, _GameplayCollisionRadius, Delay, _Priority)
	self.ChampionData[Champion] = {Name = Champion, ProjectileSpeed = ProjSpeed, GameplayCollisionRadius = _GameplayCollisionRadius, BugDelay = Delay and Delay or 0, Priority = _Priority }
end

function _Data:AddMinionData(Name, delay, ProjSpeed)
	self.MinionData[Name] = {Delay = delay, ProjectileSpeed = ProjSpeed}
end

function _Data:AddJungleMonster(Name, Priority)
	self.JungleData[Name] = Priority
end

function _Data:GetJunglePriority(Name)
	return self.JungleData[Name]
end

function _Data:AddItemData(Name, ID, RequiresTarget, Range)
	self.ItemData[ID] = _Item(Name, ID, RequiresTarget, Range)
end

function _Data:AddWardData(_CharName, _Name, _SpellName, _Type, _Range, _Duration)
	table.insert(self.WardData, {CharName = _CharName, Name = _Name, SpellName = _SpellName, Type = _Type, Range = _Range, Duration = _Duration})
end

function _Data:AddSkillData(Name, Enabled, Key, Range, DisplayName, Type, MinMana, AfterAttack, ReqAttackTarget, Speed, Delay, Width, Collision, IsReset)
	if myHero.charName == Name then
		local skill = _Skill(Enabled, Key, Range, DisplayName, Type, MinMana, AfterAttack, ReqAttackTarget, Speed, Delay, Width, Collision, IsReset)
		table.insert(self.Skills, skill)
	end
end

function _Data:GetProjectileSpeed(name)
	return self.ChampionData[name] and self.ChampionData[name].ProjectileSpeed or nil
end

function _Data:GetGameplayCollisionRadius(name)
	return self.ChampionData[name] and self.ChampionData[name].GameplayCollisionRadius or 65
end

function _Data:IsResetSpell(Spell)
	return self.ResetSpells[Spell.name]
end

function _Data:IsAttack(Spell)
	return (self.SpellAttacks[Spell.name] or Helper:StringContains(Spell.name, "attack")) and not self.NoneAttacks[Spell.name]
end

function _Data:IsJungleMinion(Object)
	return Object and Object.name and self.JungleData[Object.name] ~= nil
end

function _Data:IsCannonMinion(Minion)
	return Minion.charName:find("Cannon")
end

function _Data:IsWard(Wardd)
	for _, Ward in pairs(self.WardData) do
		if Ward.Name == Wardd.name then
			return true
		end
	end
	return false
end

function _Data:IsWardSpell(Spell)
	for _, Ward in pairs(self.WardData) do
		if Ward.SpellName:lower() == Spell.name:lower() then
			return true
		end
	end
	return false
end

function _Data:GenerateHitBoxData()
	for i = 1, heroManager.iCount do
        local hero = heroManager:GetHero(i)
        self.EnemyHitBoxes[hero.charName] = Helper:GetDistance(hero.minBBox, hero.maxBBox)
    end
	GetSave("SidasAutoCarry").EnemyHitBoxes = {TimeSaved = GetGameTimer(), Data = self.EnemyHitBoxes}
end

function _Data:LoadHitBoxData()
	local HitBoxes = GetSave("SidasAutoCarry").EnemyHitBoxes
	if HitBoxes then
		self.EnemyHitBoxes = HitBoxes.Data
	else
		self:GenerateHitBoxData()
	end
end

function _Data:GetHitBoxLastSavedTime()
	local Time = GetSave("SidasAutoCarry").EnemyHitBoxes
	if Time then
		Time = Time.TimeSaved
	else
		Time = math.huge
	end
	return Time
end

function _Data:GetOriginalHitBox(Target)
	return self.EnemyHitBoxes[Target.charName]
end

function _Data:EnemyIsImmune(Enemy)
	if self.ImmuneEnemies[Enemy.charName] then
		if Enemy.charName == "Tryndamere" and Enemy.health < MyHero:GetTotalAttackDamageAgainstTarget(Enemy) then
			return true
		elseif Enemy.charName ~= "Tryndamere" then
			return true
		end
	end
end

function _Data:GetChampionType(Champ)
	local _Type = self.ChampionData[Cham.charName].Priority

	if _Type == 1 then
		return "ADC"
	elseif _Type == 2 then
		return "AP"
	elseif _Type == 3 then
		return "Support"
	elseif _Type == 4 then
		return "Bruiser"
	elseif _Type == 5 then
		return "Tank"
	end

end

--[[ Initialize Classes ]]
function Init()
	if VIP_USER then
		if FileExist(SCRIPT_PATH..'Common/Prodiction.lua') then
			require "Prodiction"
		else
			print("[Reborn]: You need the Prodiction library from the forums!")
			return false
		end
	end
	InitAt = GetTickCount()
	AutoCarry.Skills 		= _Skills()
	AutoCarry.Keys  		= _Keys()
	AutoCarry.Items 		= _Items()
	AutoCarry.Helper 		= _Helper()
	AutoCarry.Data 			= _Data()
	AutoCarry.Jungle 		= _Jungle()
	AutoCarry.MyHero 		= _MyHero()
	AutoCarry.Minions 		= _Minions()
	AutoCarry.Crosshair 	= _Crosshair(DAMAGE_PHYSICAL, MyHero.TrueRange, 0, false, false)
	AutoCarry.Orbwalker 	= _Orbwalker()
	AutoCarry.Plugins 		= _Plugins()
	AutoCarry.Wards 		= _Wards()
	Skills, Keys, Items, Data, Jungle, Helper, MyHero, Minions, Crosshair, Orbwalker = Helper:GetClasses()
	SwingTimer 	= _SwingTimer()
	_MenuManager()
	_Drawing()
	_Summoners()
	_ChampionBuffs()
	--_AntiCancel()
	AutoCarry.Structures 	= _Structures()
	Streaming:CreateMenu()
	local _, files = ScanDirectory(BOL_PATH.."Scripts\\SidasAutoCarryPlugins")
	for _, file in pairs(files) do
		dofile(BOL_PATH.."Scripts\\SidasAutoCarryPlugins\\"..AutoCarry.Helper:TrimString(file))
	end

	if myHero.charName == "Vayne" then
		_Vayne()
	elseif myHero.charName == "Riven" then
		_Riven()
	elseif myHero.charName == "Tristana" then
		_Tristana()
	end

--	PrintSystemMessage("Valid license found. Loaded as "..(VIP_USER and "VIP" or "Non-VIP").." user.")


	--[[
			Legacy Plugin Support
			Plugins should be updated, this may be removed after a few months.
	]]

	--AutoCarry.Orbwalker = AutoCarry.Crosshair.Attack_Crosshair
	AutoCarry.SkillsCrosshair = AutoCarry.Crosshair.Skills_Crosshair
	AutoCarry.CanMove = true
	AutoCarry.CanAttack = true
	AutoCarry.MainMenu = {}
	AutoCarry.PluginMenu = nil
	AutoCarry.EnemyTable = GetEnemyHeroes()
	AutoCarry.shotFired = false
	AutoCarry.OverrideCustomChampionSupport = false
	AutoCarry.CurrentlyShooting = false
	DoneInit = true


	class '_LegacyPlugin'

	function _LegacyPlugin:__init()
		AutoCarry.PluginMenu = scriptConfig("Sida's Auto Carry Plugin: "..myHero.charName, "sidasacplugin"..myHero.charName)
		require("SidasAutoCarryPlugin - "..myHero.charName)
		PrintSystemMessage("Loaded "..myHero.charName.." plugin!")
		AddTickCallback(function() self:_OnTick() end)

		if PluginOnTick then
			AddTickCallback(function() PluginOnTick() end)
		end
		if PluginOnDraw then
			AddDrawCallback(function() PluginOnDraw() end)
		end
		if PluginOnCreateObj then
			AddCreateObjCallback(function(obj) PluginOnCreateObj(obj) end)
		end
		if PluginOnDeleteObj then
			AddDeleteObjCallback(function(obj) PluginOnDeleteObj(obj) end)
		end
		if PluginOnLoad then
			PluginOnLoad()
		end
		if PluginOnUnload then
			AddUnloadCallback(function() PluginOnUnload() end)
		end
		if PluginOnWndMsg then
			AddMsgCallback(function(msg, key) PluginOnWndMsg(msg, key) end)
		end
		if PluginOnProcessSpell then
			AddProcessSpellCallback(function(unit, spell) PluginOnProcessSpell(unit, spell) end)
		end
		if PluginOnSendChat then
			AddChatCallback(function(text) PluginOnSendChat(text) end)
		end
		if PluginOnBugsplat then
			AddBugsplatCallback(function() PluginOnBugsplat() end) 
		end
		if PluginOnAnimation then
			AddAnimationCallback(function(unit, anim) PluginOnAnimation(unit, anim) end)
		end
		if PluginOnSendPacket then
			AddSendPacketCallback(function(packet) PluginOnSendPacket(packet) end)
		end
		if PluginOnRecvPacket then
			AddRecvPacketCallback(function(packet) PluginOnRecvPacket(packet) end)
		end
		if PluginOnApplyParticle then
			AddParticleCallback(function(unit, particle) PluginOnApplyParticle(unit, particle) end)
		end
		if OnAttacked then
			RegisterOnAttacked(OnAttacked)
		end
		if PluginBonusLastHitDamage then
			Plugins:RegisterBonusLastHitDamage(PluginBonusLastHitDamage) 
		end

		if CustomAttackEnemy then
			Plugins:RegisterPreAttack(CustomAttackEnemy)
		end
	end

	function _LegacyPlugin:_OnTick()
		AutoCarry.MainMenu.AutoCarry = AutoCarryMenu.Active
		AutoCarry.MainMenu.LastHit = LastHitMenu.Active
		AutoCarry.MainMenu.MixedMode = MixedModeMenu.Active
		AutoCarry.MainMenu.LaneClear = LaneClearMenu.Active
		MyHero:MovementEnabled(AutoCarry.CanMove)
		MyHero:AttacksEnabled(AutoCarry.CanAttack)
		if #AutoCarry.EnemyTable < #Helper.EnemyTable then
			AutoCarry.EnemyTable = Helper.EnemyTable
		end
	end

	AutoCarry.GetAttackTarget = function(isCaster)
		return Crosshair:GetTarget()
	end

	AutoCarry.GetKillableMinion = function()
		return Minions.KillableMinion
	end

	AutoCarry.GetMinionTarget = function()
		return nil
	end

	AutoCarry.EnemyMinions = function()
		return Minions.EnemyMinions
	end

	AutoCarry.AllyMinions = function()
		return Minions.AllyMinions
	end

	AutoCarry.GetJungleMobs = function()
		return Jungle.JungleMonsters
	end

	AutoCarry.GetLastAttacked = function()
		return Orbwalker.LastEnemyAttacked
	end

	AutoCarry.GetNextAttackTime = function()
		return Orbwalker:GetNextAttackTime()
	end

	AutoCarry.CastSkillshot = function (skill, target)
            if VIP_USER then
                pred = TargetPredictionVIP(skill.range, skill.speed*1000, (skill.delay/1000 - (GetLatency()/2)/1000), skill.width)
            elseif not VIP_USER then
                pred = TargetPrediction(skill.range, skill.speed, skill.delay, skill.width)
            end
            local predPos = pred:GetPrediction(target)
            if predPos and Helper:GetDistance(predPos) <= skill.range then
                if VIP_USER then --TODO
                    if not skill.minions or not AutoCarry.GetCollision(skill, myHero, predPos) then
                       CastSpell(skill.spellKey, predPos.x, predPos.z)
                    end
                elseif not VIP_USER then
                    if not skill.minions or not AutoCarry.GetCollision(skill, myHero, predPos) then
                            CastSpell(skill.spellKey, predPos.x, predPos.z)
                    end
                end
            end
        end

	AutoCarry.GetCollision = function (skill, source, destination)
		if VIP_USER then
			local col = Collision(skill.range, skill.speed*1000 , (skill.delay/1000 - (GetLatency()/2)/1000), skill.width)
			return col:GetMinionCollision(source, destination)
		else
			return willHitMinion(destination, skill.width)
		end
	end

	AutoCarry.GetPrediction = function(skill, target)
		if VIP_USER then
			pred = TargetPredictionVIP(skill.range, skill.speed*1000, skill.delay/1000, skill.width)
		elseif not VIP_USER then
			pred = TargetPrediction(skill.range, skill.speed, skill.delay, skill.width)
		end
		return pred:GetPrediction(target)
	end

	AutoCarry.IsValidHitChance = function(skill, target)
		return true
	end

	AutoCarry.GetProdiction = function(Key, Range, Speed, Delay, Width, Source, Callback)
		return AutoCarry.Plugins:GetProdiction(Key, Range, Speed, Delay, Width, Source, Callback)
	end

	function willHitMinion(predic, width)
		for _, minion in pairs(Minions.EnemyMinions.objects) do
			if minion ~= nil and minion.valid and string.find(minion.name,"Minion_") == 1 and minion.team ~= player.team and minion.dead == false then
				if predic ~= nil then
					ex = player.x
					ez = player.z
					tx = predic.x
					tz = predic.z
					dx = ex - tx
					dz = ez - tz
					if dx ~= 0 then
						m = dz/dx
						c = ez - m*ex
					end
					mx = minion.x
					mz = minion.z
					distanc = (math.abs(mz - m*mx - c))/(math.sqrt(m*m+1))
					if distanc < width and math.sqrt((tx - ex)*(tx - ex) + (tz - ez)*(tz - ez)) > math.sqrt((tx - mx)*(tx - mx) + (tz - mz)*(tz - mz)) then
						return true
					end
				end
			end
		end
		return false
	end

	if FileExist(LIB_PATH .."SidasAutoCarryPlugin - "..myHero.charName..".lua") then
	    _LegacyPlugin()
	end
end


--[[
		Custom Champion Support
]]

class '_Tristana'

function _Tristana:__init()
	AddTickCallback(function() self:_OnTick() end)
end

function _Tristana:_OnTick()
	local SkillE = Skills:GetSkill(_E)
	local range = MyHero.TrueRange
	if SkillE then
		local target = Crosshair:GetTarget()
		if Orbwalker:CanOrbwalkTarget(target) then
			range = Helper:GetDistance(target)
		end

		SkillE.Range = range
	end
end

class '_Riven'

function _Riven:__init()
	self.lastQ = 0
	Orbwalker._OnProcessSpell = function(_, Unit, Spell) self:ProcSpell(_, Unit, Spell) end
	self.StartedOnAttacked = 0
	AddProcessSpellCallback(function(Unit, Spell) self:_OnProcessSpell(Unit, Spell) end)
end

function _Riven:ProcSpell(_, Unit, Spell)
	if Unit.isMe and GetTickCount() > self.lastQ + 150 then
		if Data:IsAttack(Spell) then
			Orbwalker.LastAttack = GetTickCount() - GetLatency() / 2
			Orbwalker.LastWindUp = Spell.windUpTime * 1000
			Orbwalker.LastAttackCooldown = Spell.animationTime * 1000
			Orbwalker.AttackCompletesAt = Orbwalker.LastAttack + Orbwalker.LastWindUp
			Orbwalker.LastAttackSpeed = myHero.attackSpeed
			Orbwalker.DoneOnAttacked = false
			MyHero.DonePreAttack = false
			self.StartedOnAttacked = GetTickCount()
		elseif Data:IsResetSpell(Spell) then
			Orbwalker:ResetAttackTimer()
		end
	elseif Unit.type == myHero.type and Unit.team == myHero.team then
		if Data:IsAttack(Spell) then
			local _Row = {WindUp = Spell.windUpTime * 1000, Cooldown = Spell.animationTime * 1000}
			Orbwalker.Allies[Unit.networkID] = _Row
		end
	end
end


function _Riven:_OnProcessSpell(Unit, Spell)
	if Unit.isMe and Spell.name == "RivenTriCleave" then
		self.lastQ = GetTickCount()
	end
end

class '_Vayne'

function _Vayne:__init()

	self.bushWardPos = nil
	self.tp = TargetPredictionVIP(1000, 2200, 0.25)
	self.SpellData = {}
	self.SpellExpired = true

	AddTickCallback(function() self:_OnTick() end)
	AddProcessSpellCallback(function(Unit, Spell) self:_OnProcessSpell(Unit, Spell) end)

	VayneMenu = scriptConfig("Sida's Auto Carry Reborn: Vayne", "sidasacvayne")
	VayneMenu:addSubMenu("Configuration", "sidasacvaynesub")
	VayneMenu:addSubMenu("Allowed Condemn Targets", "sidasacvayneallowed")
	
	VayneMenu:addParam("toggleMode", "Toggle Mode (Requires Reload)", SCRIPT_PARAM_ONOFF, false)
	if VayneMenu.toggleMode then
		VayneMenu:addParam("Enabled", "Auto-Condemn", SCRIPT_PARAM_ONKEYTOGGLE, true, GetKey("N"))
	else
		VayneMenu:addParam("Enabled", "Auto-Condemn", SCRIPT_PARAM_ONKEYDOWN, false, 32)
	end
	VayneMenu:permaShow("Enabled")
	
	
	VayneMenu.sidasacvaynesub:addParam("condemnClosers", "Auto-Condemn Gap Closers", SCRIPT_PARAM_ONOFF, true)
	VayneMenu.sidasacvaynesub:addParam("pushDistance", "Max Condemn Distance", SCRIPT_PARAM_SLICE, 300, 0, 450, 0)
	VayneMenu.sidasacvaynesub:addParam("condemnSAC", "Only condemn Reborn target", SCRIPT_PARAM_ONOFF, true)
	VayneMenu.sidasacvaynesub:addParam("useWard", "Auto-Trinket Bush", SCRIPT_PARAM_ONOFF, true)

	for i, Enemy in pairs(GetEnemyHeroes()) do
		VayneMenu.sidasacvayneallowed:addParam("enabled"..Enemy.charName, Enemy.charName, SCRIPT_PARAM_ONOFF, true)
	end
end

function _Vayne:DoCondemn(Enemy)
	local EnemyPos = VIP_USER and self.tp:GetPrediction(Enemy) or Enemy
	local PushPos = EnemyPos + (Vector(EnemyPos) - myHero):normalized() * VayneMenu.sidasacvaynesub.pushDistance
	if Enemy.x > 0 and Enemy.z > 0 then
	    local checks = math.ceil((VayneMenu.sidasacvaynesub.pushDistance) / 65)
	    local checkDistance = (VayneMenu.sidasacvaynesub.pushDistance) / checks
	    local InsideTheWall = false
	    local checksPos
	    for k=1, checks, 1 do
	        checksPos = Enemy + (Vector(Enemy) - myHero):normalized() * (checkDistance * k)
	        local WallContainsPosition = IsWall(D3DXVECTOR3(checksPos.x, checksPos.y, checksPos.z))
	        if WallContainsPosition then
	            InsideTheWall = true
	            break
	        end
	    end

	    if InsideTheWall then 
	    	CastSpell(_E, Enemy) 
	    	if checksPos and VayneMenu.sidasacvaynesub.useWard then
	    		local bushPos = self:FindNearestNonWall(checksPos.x, checksPos.y, checksPos.z, 100, 20)
	    		if bushPos and (IsWallOfGrass(bushPos)) then
	    			self.bushWardPos = {Pos = bushPos, TimeOut = GetTickCount() + 2000, Target = Enemy}
	    		end
	    	end
	    end
	end
end

function _Vayne:_OnTick()
	if self.bushWardPos then
		if GetTickCount() < self.bushWardPos.TimeOut then
			if not self.bushWardPos.Target.visible then
				local bushPos = self:FindNearestBushSpot(self.bushWardPos.Pos)
				if Helper:GetDistance(bushPos) <= 545 then
					CastSpell(ITEM_7, bushPos.x, bushPos.z)
				end
			end
		else
			self.bushWardPos = nil
		end
	end

	if VayneMenu.Enabled and myHero:CanUseSpell(_E) == READY then
		if VayneMenu.sidasacvaynesub.condemnClosers then
            if not self.SpellExpired and (GetTickCount() - self.SpellData.spellCastedTick) <= (self.SpellData.spellRange/self.SpellData.spellSpeed) * 1000 then
                local spellDirection     = (self.SpellData.spellEndPos - self.SpellData.spellStartPos):normalized()
                local spellStartPosition = self.SpellData.spellStartPos + spellDirection
                local spellEndPosition   = self.SpellData.spellStartPos + spellDirection * self.SpellData.spellRange
                local heroPosition = Point(myHero.x, myHero.z)

                local lineSegment = LineSegment(Point(spellStartPosition.x, spellStartPosition.y), Point(spellEndPosition.x, spellEndPosition.y))

                if lineSegment:distance(heroPosition) <= (not self.SpellData.spellIsAnExpetion and 65 or 200) then
                    CastSpell(_E, self.SpellData.spellSource)
                end
            else
                self.SpellExpired = true
                self.SpellData = {}
            end
        end

		if VayneMenu.sidasacvaynesub.condemnSAC then
			local Target = AutoCarry.Crosshair.Attack_Crosshair.target
			if ValidTarget(Target, 725) then
				self:DoCondemn(Target)
			end
		else
			for _, Enemy in pairs(GetEnemyHeroes()) do
				if VayneMenu.sidasacvayneallowed["enabled"..Enemy.charName] and ValidTarget(Enemy, 725) then
					self:DoCondemn(Enemy)
				end
			end
		end
	end
end

function _Vayne:FindNearestBushSpot(Pos)
	local lastBush = Pos
	local Distance = Helper:GetDistance(Pos)
	MyPos = Vector(myHero.x, myHero.y, myHero.z)

	for i = Distance, 0, -1 do
		endPos = Vector(Pos.x, Pos.y, Pos.z)
		checkPos = MyPos - (MyPos - endPos):normalized() * i
		if IsWallOfGrass(D3DXVECTOR3(checkPos.x, checkPos.y, checkPos.z)) then
			lastBush = MyPos - (MyPos - endPos):normalized() * (i + 10)
		else
			break
		end
	end
	return lastBush
end

-- Credits to vadash
function _Vayne:FindNearestNonWall( x0, y0, z0, maxRadius, precision )
    if not IsWall(D3DXVECTOR3(x0, y0, z0)) then return nil end
    local radius, gP = 1, precision or 50
    x0, y0, z0, maxRadius = math.round(x0/gP)*gP, math.round(y0/gP)*gP, math.round(z0/gP)*gP, maxRadius and math.floor(maxRadius/gP) or math.huge
    local function toGamePos(x, y) return x0+x*gP, y0, z0+y*gP end
    while radius<=maxRadius do
        for i = 1, 4 do
           local p = D3DXVECTOR3(toGamePos((i==2 and radius) or (i==4 and -radius) or 0,(i==1 and radius) or (i==3 and -radius) or 0))
           if not IsWall(p) then return p end
        end
        local f, x, y = 1-radius, 0, radius
        while x<y-1 do
            x = x + 1
            if f < 0 then f = f+1+x+x
            else y, f = y-1, f+1+x+x-y-y end
            for i=1, 8 do
                local w = math.ceil(i/2)%2==0
                local p = D3DXVECTOR3(toGamePos(((i+1)%2==0 and 1 or -1)*(w and x or y),(i<=4 and 1 or -1)*(w and y or x)))
                if not IsWall(p) then return p end
            end
        end
        radius = radius + 1
    end
end

function _Vayne:_OnProcessSpell(Unit, Spell)
	if not VayneMenu.sidasacvaynesub.condemnClosers then
		return
	end

	local GetGapCloser = {
	    ['Aatrox']      = {true, spell = _Q,                  range = 1000,  projSpeed = 1200, },
        ['Akali']       = {true, spell = _R,                  range = 800,   projSpeed = 2200, }, 
        ['Alistar']     = {true, spell = _W,                  range = 650,   projSpeed = 2000, }, 
        ['Diana']       = {true, spell = _R,                  range = 825,   projSpeed = 2000, }, 
        ['Gragas']      = {true, spell = _E,                  range = 600,   projSpeed = 2000, },
        ['Graves']      = {true, spell = _E,                  range = 425,   projSpeed = 2000, exeption = true },
        ['Hecarim']     = {true, spell = _R,                  range = 1000,  projSpeed = 1200, },
        ['Irelia']      = {true, spell = _Q,                  range = 650,   projSpeed = 2200, }, 
        ['JarvanIV']    = {true, spell = jarvanAddition,      range = 770,   projSpeed = 2000, }, -- Skillshot/Targeted ability
        ['Jax']         = {true, spell = _Q,                  range = 700,   projSpeed = 2000, }, 
        ['Jayce']       = {true, spell = 'JayceToTheSkies',   range = 600,   projSpeed = 2000, }, 
        ['Khazix']      = {true, spell = _E,                  range = 900,   projSpeed = 2000, },
        ['Leblanc']     = {true, spell = _W,                  range = 600,   projSpeed = 2000, },
        ['LeeSin']      = {true, spell = 'blindmonkqtwo',     range = 1300,  projSpeed = 1800, },
        ['Leona']       = {true, spell = _E,                  range = 900,   projSpeed = 2000, },
        ['Malphite']    = {true, spell = _R,                  range = 1000,  projSpeed = 1500 + Unit.ms},
        ['Maokai']      = {true, spell = _Q,                  range = 600,   projSpeed = 1200, }, 
        ['MonkeyKing']  = {true, spell = _E,                  range = 650,   projSpeed = 2200, }, 
        ['Pantheon']    = {true, spell = _W,                  range = 600,   projSpeed = 2000, }, 
        ['Poppy']       = {true, spell = _E,                  range = 525,   projSpeed = 2000, }, 
        ['Renekton']    = {true, spell = _E,                  range = 450,   projSpeed = 2000, },
        ['Sejuani']     = {true, spell = _Q,                  range = 650,   projSpeed = 2000, },
        ['Shen']        = {true, spell = _E,                  range = 575,   projSpeed = 2000, },
        ['Tristana']    = {true, spell = _W,                  range = 900,   projSpeed = 2000, },
        ['Tryndamere']  = {true, spell = 'Slash',             range = 650,   projSpeed = 1450, },
        ['XinZhao']     = {true, spell = _E,                  range = 650,   projSpeed = 2000, }, 
    }

    if Unit.type == myHero.type and Unit.team ~= myHero.team and GetGapCloser[Unit.charName] and Helper:GetDistance(Unit) < 2000 and Spell ~= nil then
        if Spell.name == (type(GetGapCloser[Unit.charName].spell) == 'number' and Unit:GetSpellData(GetGapCloser[Unit.charName].spell).name or GetGapCloser[Unit.charName].spell) then
            if Spell.target and Spell.target.name == myHero.name or GetGapCloser[Unit.charName].spell == 'blindmonkqtwo' then
                CastSpell(_E, Unit)
            else
                self.SpellExpired = false
                self.SpellData = {
                    spellSource = Unit,
                    spellCastedTick = GetTickCount(),
                    spellStartPos = Point(Spell.startPos.x, Spell.startPos.z),
                    spellEndPos = Point(Spell.endPos.x, Spell.endPos.z),
                    spellRange = GetGapCloser[Unit.charName].range,
                    spellSpeed = GetGapCloser[Unit.charName].projSpeed,
                    spellIsAnExpetion = GetGapCloser[Unit.charName].exeption or false,
                }
            end
        end
    end
end

--[[ Cone Helper by llama ]]

function areClockwise(testv1,testv2)
    return -testv1.x * testv2.y + testv1.y * testv2.x>0 --true if v1 is clockwise to v2
end
function sign(x)
    if x> 0 then return 1
    elseif x<0 then return -1
    end
end
function GetCone(radius,theta)
    --Build table of enemies in range
    n = 1
    v1,v2,v3 = 0,0,0
    largeN,largeV1,largeV2 = 0,0,0
    theta1,theta2,smallBisect = 0,0,0
    coneTargetsTable = {}
   
    for i = 1, heroManager.iCount, 1 do
    hero = heroManager:getHero(i)
    if ValidTarget(hero,radius) then-- and inRadius(hero,radius*radius) then
            coneTargetsTable[n] = hero
            n=n+1
        end
    end

    if #coneTargetsTable>=2 then -- true if calculation is needed
    --Determine if angle between vectors are < given theta
        for i=1, #coneTargetsTable,1 do
            for j=1,#coneTargetsTable, 1 do
                if i~=j then
                    --Position vector from player to 2 different targets.
                    v1 = Vector(coneTargetsTable[i].x-player.x , coneTargetsTable[i].z-player.z)
                    v2 = Vector(coneTargetsTable[j].x-player.x , coneTargetsTable[j].z-player.z)
                    thetav1 = sign(v1.y)*90-math.deg(math.atan(v1.x/v1.y))
                    thetav2 = sign(v2.y)*90-math.deg(math.atan(v2.x/v2.y))
                    thetaBetween = thetav2-thetav1                 

                    if (thetaBetween) <= theta and thetaBetween>0 then --true if targets are close enough together.
                            if #coneTargetsTable == 2 then --only 2 targets, the result is found.
                                largeV1 = v1
                                largeV2 = v2
                            else
                                --Determine # of vectors between v1 and v2                                                     
                                tempN = 0
                                for k=1, #coneTargetsTable,1 do
                                    if k~=i and k~=j then
                                        --Build position vector of third target
                                        v3 = Vector(coneTargetsTable[k].x-player.x , coneTargetsTable[k].z-player.z)
                                        --For v3 to be between v1 and v2
                                        --it must be clockwise to v1
                                        --and counter-clockwise to v2
                                        if areClockwise(v3,v1) and not areClockwise(v3,v2) then
                                            tempN = tempN+1
                                        end
                                    end
                                end
                                if tempN > largeN then
                                --store the largest number of contained enemies
                                --and the bounding position vectors
                                    largeN = tempN
                                    largeV1 = v1
                                    largeV2 = v2
                                end
                            end
                        end
                    end
                end
            end
    elseif #coneTargetsTable==1 then
        return coneTargetsTable[1]
    end
   
    if largeV1 == 0 or largeV2 == 0 then
    --No targets or one target was found.
            return nil
    else
    --small-Bisect the two vectors that encompass the most vectors.
        if largeV1.y == 0 then
            theta1 = 0
        else
            theta1 = sign(largeV1.y)*90-math.deg(math.atan(largeV1.x/largeV1.y))
        end
        if largeV2.y == 0 then
            theta2 = 0
        else
            theta2 = sign(largeV2.y)*90-math.deg(math.atan(largeV2.x/largeV2.y))
        end

        smallBisect = math.rad((theta1 + theta2) / 2)
        vResult = {}
        vResult.x = radius*math.cos(smallBisect)+player.x
        vResult.y = player.y
        vResult.z = radius*math.sin(smallBisect)+player.z
       
        return vResult
    end
end

math.randomseed(tonumber(tostring(os.time()):reverse():sub(1,6))*tonumber(myHero.health)+tonumber(myHero.networkID))
lTick = math.random(1,999999)
local cBa
function RunLoad()

	PrintSystemMessage("Authenticating...")
	if debug.getinfo and debug.getinfo(_G.GetUser).what == "C" then
		cBa = _G.GetUser
		_G.GetUser = function() return end
		if debug.getinfo(_G.GetUser).what == "Lua" then
			_G.GetUser = cBa
			chkUsr()
		end
	else
		PrintSystemMessage("Another script is interfering with Reborn!")
	end
end

function chkUsr()
	local usrs = {
		"jbman",
		"pim",
		"Exodus885",
		"weeeqt",
		"Keoshin",
		"kevinkev",
		"johndoe",
		"kev",
		"Cabana",
		"laughing2g",
		"mudge",
		"Pickonme",
		"andreksu",
		"spydre",
		"kirewade",
		"birdpoodan",
		"jarvis101",
		"challenger12345",
		"Omnipot3nt",
		"Godhammer",
		"allstar69",
		"reinhartXIV",
		"eunn",
		"yukyusan",
		"DivineMethod",
		"igotcslol",
		"jta87k",
		"apple",
		"skitzo13337",
		"418727631",
		"stein121",
		"tmilam",
		"lolnodawg",
		"lucas22490",
		"eachzin",
		"zedneedbeer",
		"xxlarsyxx",
		"bhoffman",
		"qwe",
		"lordfinder",
		"pew",
		"everrich",
		"Jokelas",
		"cbkixo",
		"lolcheat321",
		"gianmaranon",
		"lucass",
		"hard_as_steel",
		"johnmgee",
		"ohdaym",
		"tostii",
		"xin265755",
		"chunchun",
		"Virus1x",
		"diyosako",
		"hodge",
		"Onion",
		"lnteractive",
		"relaxkid",
		"Racutt",
		"andysmalll",
		"smetsson",
		"William Hoff",
		"Natethegreat",
		"remus3",
		"pompous_yahtzee",
		"schabba",
		"methodxb",
		"hellking298",
		"duartex93",
		"ljk3322",
		"PainLess",
		"chancity",
		"Diwas89",
		"TheCracker",
		"kriksi",
		"larby",
		"x7x",
		"snapztepwn",
		"anubis342",
		"iamamazing",
		"desa",
		"takanorisk2",
		"asusdell",
		"el mamuth",
		"chowdernguyen",
		"naruto",
		"Lukeyboy89",
		"ijustwannaleech",
		"cuongvu00",
		"nommi",
		"Sparx",
		"Rompeansikt",
		"pok",
		"Lucky22",
		"xtony211",
		"lolhi",
		"ghostrider9310",
		"favas22",
		"bobbyjayblack",
		"heist",
		"lordorion420",
		"Rainydays",
		"oliveira2201",
		"Remonstrate",
		"guru006",
		"jackauffman",
		"benito18",
		"vicroram",
		"leqos",
		"peppoonline",
		"zikenzie",
		"xffffa",
		"nightingale737",
		"Gintoci",
		"legotya",
		"moggflunkie",
		"sharge",
		"Batcan0704",
		"CrusherTheKing94",
		"griz",
		"tissues",
		"oxeem",
		"spaceface",
		"totos2",
		"L4a",
		"slashxcdoe",
		"local303",
		"hayakio",
		"dawnera",
		"madiqhertz",
		"pqmailer",
		"thunderbow9",
		"lajurasaca",
		"Guu",
		"mewkyy",
		"Accolade83",
		"broplay",
		"EsPi92",
		"spidermon",
		"praNNkii",
		"sillyfang",
		"raceu2hell",
		"golgari01",
		"unkn",
		"ambienwalrus",
		"klaasien",
		"jakesmurf",
		"Incognito",
		"xion3",
		"merark",
		"Erwinbeck",
		"RankMaster",
		"Eevi",
		"leerk",
		"alexeipetrovich",
		"iodas1",
		"IkaEren",
		"afh100",
		"Fbr",
		"farshmak",
		"teino",
		"nevalopo",
		"firerebel",
		"sniperbro",
		"Wrenkla",
		"siRi85",
		"Faeratic",
		"grapesodur",
		"leupoma",
		"vadash",
		"abortion",
		"fluctu8",
		"bnbhvsh",
		"valdeze",
		"bnbhvsh",
		"web38",
		"kostaman",
		"maxemz",
		"kingkidd",
		"neyu",
		"maxemz",
		"configz",
		"nicholasrowan",
		"-unkn0wn-",
		"frozer",
		"TheBotKing",
		"floresrikko",
		"melody",
		"antihero",
		"aricias",
		"kaspars",
		"bistom",
		"peterpecker",
		"benn046",
		"vald",
		"oliver5502",
		"idylkarthus",
		"fawkstrot",
		"rene_gahe",
		"goldmaxter",
		"g0rning",
		"wgmiskel",
		"shadybs",
		"Semtize",
		"darkhao",
		"dimas2013",
		"engies",
		"a822022",
		"boboben1",
		"st1ck",
		"Menime",
		"milchstrudel",
		"armagedonas",
		"Fizzster",
		"taistelu",
		"vellusta",
		"Ymiron",
		"420yoloswag",
		"xalyx",
		"atL",
		"yrmom3141",
		"Phebos",
		"carl0sfandango",
		"Rosso",
		"kaiori93",
		"mrsynix",
		"Criesisangel",
		"Naqoyqatsi",
		"makelovein",
		"UwshUwerMe",
		"gwfreak01",
		"Vortur",
		"Mikkeson",
		"maniaclucas",
		"chiefcharlie",
		"chills",
		"Babs",
		"endinglegacy",
		"holyshyt",
		"srex",
		"lysoll",
		"omegabeast14",
		"slice2013",
		"listerkeler",
		"Fruit",
		"q8sora",
		"z1ppo31",
		"xtorsinj",
		"nizahe",
		"neekeri22",
		"k1x",
		"silverjax",
		"tandu",
		"Akasai5",
		"zanzar",
		"megapillar",
		"Jackypaul",
		"Skito",
		"unrulypenguin",
		"EZGAMER",
		"Cookiezi",
		"bewild31",
		"xll.de",
		"Jcannon",
		"Witagi",
		"collster37",
		"robinhood",
		"TheRoyalYordle",
		"billyf209",
		"fubr0901",
		"Guadostar",
		"varity",
		"zikkah",
		"haxn23",
		"mwow1",
		"manuel91",
		"funsize",
		"xiaomage",
		"uberfox3893",
		"bk201",
		"nerf777",
		"verajicus",
		"Hans2k13",
		"hyper689",
		"exile",
		"supersonictr",
		"wukeokok",
		"Hybrin",
		"rtstrauma",
		"khicon",
		"Krewellavie",
		"n1ce0n3",
		"Domincii",
		"chickenspank",
		"Hatemost",
		"watercooled",
		"Fireworka",
		"masterstrike",
		"martsen244",
		"tienermoeder",
		"kamonisin",
		"agn11059555",
		"flavor2443",
		"xeph",
		"myroomun",
		"dydgks88",
		"oleyyh",
		"aki45",
		"adz85",
		"bobenverlan",
		"nil",
		"scarem",
		"alannismason",
		"karman",
		"empty1991",
		"razer424",
		"blacksnow",
		"asiangirl",
		"Nemes1s",
		"liquidace",
		"Modern Algebra",
		"rafaelinux",
		"Airwavez",
		"Cannings",
		"leandro95",
		"puttrik",
		"ejdernefesi",
		"Tickbird",
		"GAMEPOINTSBR",
		"Reason",
		"Pkz",
		"buzzard",
		"iJuno",
		"thechancelor",
		"Kain",
		"Prototype23",
		"curtyo",
		"arigold",
		"D3boi",
		"toteak",
		"tamalok",
		"firez0r",
		"Dottie",
		"merark",
		"shaunyboi",
		"xiiiii",
		"dosentmatter123",
		"blubbb",
		"string",
		"Lolinusa",
		"Johay",
		"lukout",
		"FalafelVegan",
		"nedved94",
		"akzification",
		"fedex123",
		"ri0tkid",
		"fezi",
		"xpain",
		"nullifier",
		"dionszj",
		"dienofail",
		"mkwarrior",
		"einkeks95",
		"Apeeh",
		"marcosd",
		"Disassembled",
		"andrethegiant",
		"sunnr",
		"Uruu",
		"indecisions",
		"cinderlol",
		"jonas9513",
		"plobrother",
		"kennubis",
		"jiimmyp",
		"Si1da",
		"Briddle85",
		"obfusk8",
		"sachix",
		"Paradoxel",
		"getsnipeddown",
		"UglyOldGuy",
		"Nevrack",
		"maddog00700",
		"jeepster2k3",
		"CoDice",
		"botshotgg",
		"mRSheeN",
		"xetrok",
		"Kabouter",
		"ljk1291",
		"NoG",
		"aNNdii",
		"recoba20",
		"IronJokers",
		"alfahad",
		"Skito",
		"blackrose180",
		"smypm",
		"toretrax",
		"onc86",
		"kfraser95",
		"drizrysn",
		"Eminent85",
		"toxicteddy",
		"InfiniZ",
		"andre1212",
		"xsanikax",
		"darkdusk",
		"ghostshank",
		"dcnews",
		"golona123",
		"nizadar",
		"tutuxich",
		"xpersona",
		"sehcure",
		"Trace27",
		"wojt5",
		"solclo",
		"PixieDuster",
		"nab742",
		"keke",
		"phbn93",
		"tehuser",
		"wanger26",
		"jakdo",
		"dzombz",
		"q179339065",
		"traktor",
		"bearsmacked",
		"dxbkillerman2",
		"deanster5",
		"ms6952",
		"s1lent",
		"katrini",
		"leon8288",
		"pqs",
		"pyrophenix",
		"pbthug91",
		"patton319",
		"totallytrav",
		"ailikes",
		"solenrus",
		"tjtjsqh",
		"Ragekid",
		"connormcg",
		"koufuku",
		"jjas0n",
		"thekevjaa",
		"xpliclt",
		"Quickdraw",
		"niels306",
		"xTc1992",
		"n3mezis",
		"blackhype",
		"Bankreis",
		"kihan112",
		"kirajoker",
		"razzko",
		"rxemi",
		"zephyr",
		"elanator",
		"lolhi",
		"tipanaya",
		"Littlejake",
		"chinkinuniform",
		"hardcider",
		"geheim",
		"pgmlito",
		"rrelaxing",
		"osteocyte",
		"Clamity",
		"turtlebot",
		"Alkanna",
		"SweetDreams",
		"bensom6",
		"WhiteKnight",
		"midnight123",
		"klokje",
		"Sephiroth D2k",
		"Alvesik",
		"Mercurial4991",
		"roxterrocking",
		"thegunster",
		"Sabaku",
		"stoon",
		"privilegue",
		"anow2",
		"xxgowxx",
		"poke",
		"whyld",
		"Phexon",
		"veers13",
		"tr1p0d",
		"killerklownskill",
		"visionsz",
		"hex69",
		"object",
		"Darkraiser",
		"GetSnipedDown",
		"Emiroe",
		"xuzhe56828608",
		"lassiemeow",
		"nebuer",
		"plebs01",
		"conflicterrr",
		"nimus14",
		"Skito",
		"Tux",
		"operia2",
		"Yoshara",
		"Torus",
		"Mirage",
		"bass",
		"Mordraug",
		"WEEDZOR",
		"ITryNotToTroll",
		"16hex16",
		"steffen1249",
		"bigboom",
		"akjnj848",
		"shanye",
		"c0ngs",
		"chewbaca",
		"sathdi",
		"DaAlmyte",
		"Kinshien",
		"johnnywalker",
		"Bunzip",
		"Phoeb",
		"manolo",
		"troni1278",
		"kcire",
		"Zemise",
		"klokje",
		"Ext",
		"iuser99",
		"xdedde",
		"Thrill Drill",
		"DestinyGame",
		"masterllama",
		"lolbotgg",
		"Baule17",
		"TruePRD",
		"jarnexs",
		"toddreesehacker",
		"tashin",
		"karlheinz",
		"Crackle",
		"kronicle1",
		"tiroz",
		"SurrealPower",
		"methodxb",
		"orgamarius",
		"barad3eey",
		"xvartx",
		"FPSabby",
		"H4CK",
		"dreamn",
		"zzyzxer",
		"loljonas",
		"jujupie",
		"xxbrteamxx",
		"Npau",
		"ifizze",
		"Hassliebe",
		"Killaaaa",
		"shout",
		"orlandofua",
		"zunri",
		"skinalt",
		"aboose",
		"wiedzminek",
		"morza",
		"TheOneRU",
		"dupliicity",
		"bebie",
		"kakulukian",
		"Rezlol",
		"tlimzor",
		"Lexxes",
		"LukrativeHD",
		"ioedaq4",
		"shawnpfadenhauer",
		"zephy",
		"Brok3nz",
		"pumadanny",
		"quixor329",
		"whitewolf",
		"syraxtepper",
		"qq2594467440",
		"522868600",
		"nekomimibadik",
		"kleksskill",
		"timmy16744",
		"osmandusman",
		"koerdum",
		"soppy",
		"hahahax",
		"deeka",
		"dmassa",
		"countergamer24",
		"inyu",
		"limit",
		"dmassa",
		"blackiechan",
		"Chriski",
		"SideSteal",
		"skaycut",
		"bombboy420",
		"gee4hire",
		"omiie",
		"t3k",
		"aking92",
		"149kg",
		"LoadingGodMode",
		"sangukk",
		"Nomatra",
		"thelion",
		"ZheFish",
		"Seamlessly",
		"kazie",
		"MoobyB",
		"bestrobber97",
		"Kaotik",
		"Dikki",
		"omgitschaz",
		"nicofasho",
		"coolguy85",
		"ongie119",
		"spydre",
		"justatest90",
		"gigalord",
		"Leweh",
		"Haliax",
		"Xero666",
		"Farissi",
		"hoodrichjosh",
		"Zevksis",
		"Lazer713",
		"khuong",
		"manmode69",
		"pim",
		"chewbaca",
		"Risurigami",
		"Luxlux",
		"solofire",
		"banned4haxx",
		"jaredking10",
		"aLorzy91",
		"Wiader",
		"b4lfhed1",
		"decaed",
		"Teargas111",
		"DRxIGNORANCE",
		"kaminarihime",
		"Alterblack03",
		"2118114",
		"joeyzbg",
		"cupidsrage",
		"nestle",
		"theapemancometh",
		"hydeist",
		"drcat",
		"EnigmaBotr",
		"nickpooz",
		"lagarchadelmono4",
		"vegnagunz",
		"MellowPenguin",
		"klokeloke",
		"Stormy21",
		"swtouiguihunibun",
		"wonkee",
		"bobafett141",
		"zudren",
		"Drak",
		"dacrash2",
		"johndoe",
		"desperatepower",
		"mootme1",
		"clbraver",
		"hestia",
		"takeavvay",
		"malaniless",
		"bedaky",
		"Davids",
		"robban391",
		"Nayhorrorn",
		"jebou9",
		"Anik",
		"kobe2324",
		"Windson18",
		"Peakzr",
		"gulptryne",
		"sifika",
		"billybro",
		"r3dcod3",
		"XII Future",
		"adz85",
		"mma4tw",
		"renews",
		"N0xZz",
		"Amannda",
		"felipehso",
		"Valdar",
		"Spraggins",
		"Titan",
		"ChrisSetzer",
		"tutuxich",
		"fedor",
		"Dyingman101",
		"olax",
		"Osaka",
		"IPFR33LY",
		"krv",
		"jayzon070",
		"valo101",
		"rayvagio",
		"ragekid",
		"kingish",
		"hoempa",
		"funkel1989",
		"wick",
		"fifawinner",
		"ilumoif260",
		"Pider",
		"alannismason",
		"evolucian",
		"krossx123",
		"night3",
		"zyjcxc",
		"sUPREMeforce",
		"CodeX1337",
		"zamorakje",
		"VilePillager",
		"deezo",
		"fueledbyrainbows",
		"Noize",
		"notanyoneknows",
		"Okkiru",
		"paradoXXX",
		"WretchedEvil",
		"smallbooty",
		"NerdMaromba",
		"zeonx delta",
		"Girthe",
		"meguschta",
		"bibanu",
		"greatrift",
		"Sonat",
		"krosano",
		"winankid",
		"Johannssonn",
		"augustusecnarf",
		"iAdam",
		"minimoney1",
		"Chronic",
		"Yokardus",
		"oddessax",
		"swain",
		"1337xgamer",
		"whiskey1025",
		"LHR",
		"salNwa",
		"post8",
		"shakure",
		"atz513",
		"keloke",
		"doidexo",
		"eXit",
		"silent84",
		"fragmot",
		"justinchoy",
		"holden33",
		"rprp",
		"pisto",
		"amiarobot",
		"bestplox",
		"ioedaq4",
		"hl3323999",
		"Linus Van Pelt",
		"nick5020",
		"bobbobbob",
		"Marco727",
		"Jed",
		"MonkeyKid",
		"bruunow",
		"getsnipeddown",
		"vcvanillacoke",
		"BadStrip",
		"kobe2324",
		"shjordan",
		"shadowanger",
		"warpudge",
		"magmia",
		"BotO256",
		"challenger12345",
		"Vital",
		"jewish",
		"hans_meier",
		"eddow",
		"cronicsb",
		"sirpoof",
		"Dreamful92",
		"heelx",
		"Xendz",
		"eway86",
		"xrated",
		"user_nix",
		"fr0ta4",
		"rileylol",
		"Arcueid",
		"Furiouslol",
		"nism",
		"kirajoker",
		"godly50",
		"Tovddi",
		"gatugeniet",
		"Ms El Jefe",
		"traxx",
		"Pawemol12",
		"lethalds",
		"bowser",
		"Battler624",
		"challenger12345",
		"lucas_2100",
		"nyx",
		"HCVike",
		"SilentBot",
		"impaq",
		"Setsna",
		"duzzk",
		"frost21",
		"gastan",
		"iCrawlTV",
		"Hemanshi",
		"phyrro",
		"ykle",
		"haz894",
		"weekend4tw",
		"dkolee",
		"snowmn313",
		"Vhang",
		"Bercley",
		"mullerlight",
		"apersonliving",
		"peacedude_11",
		"WTayllor",
		"zeefi",
		"V3nG3ur",
		"xtr4points",
		"AP303",
		"Gironjmo",
		"cincy91",
		"LegitMerk",
		"rzzn",
		"wo94fanni",
		"pezlar",
		"Naught",
		"max6020",
		"Eriszen",
		"dane517",
		"atajoe",
		"hakmin1217",
		"typenine",
		"alsd",
		"ganassa",
		"User44two",
		"oallan",
		"Switchy",
		"alexReady",
		"Berial",
		"xkiyusx",
		"bwahwha",
		"ogmuffin",
		"flapflop",
		"Frosttfire",
		"ballsdeep",
		"Easy as abc123",
		"frinshy",
		"xennche",
		"neinax",
		"PoireauFTW",
		"sillyspaz",
		"pissmeh",
		"Grimmerz",
		"billgoiaba",
		"herbert",
		"lolofbot",
		"kafetao",
		"kaffein",
		"deamonswag",
		"Ex0tic123",
		"iargue",
		"wack",
		"terribles",
		"rftiiv15",
		"arrio",
		"moreme",
		"wtf2020",
		"trilz87",
		"SuperLeet",
		"Synchrodoom",
		"gbashore",
		"Biddychu",
		"frylockxxx",
		"Sheidaka",
		"Robtomo",
		"Draesia",
		"soviet_jax",
		"feralnub",
		"Mazer2002",
		"verxx",
		"popster",
		"Vex",
		"pokerdream42",
		"st4ck3r",
		"echomango",
		"KatarinaIsGodly",
		"jmscruzado",
		"chrisokgo",
		"sanjok1991",
		"cuzzer",
		"theapemancometh",
		"krzysiekd",
		"arumba",
		"xkellettx",
		"orenalb",
		"wack",
		"keithwilk28",
		"splinters2",
		"xzz",
		"bibow06",
		"silly3648",
		"zonker",
		"drewgundy",
		"Brez",
		"vinah",
		"rxrickify",
		"fakeangels3",
		"nightly1029",
		"marlow1337",
		"gregt",
		"tac",
		"Rosenrot",
		"Cake",
		"challenger12345",
		"Urged",
		"dadeus",
		"Eanzos",
		"Myndtrick",
		"sillyfang",
		"immortalhz",
		"sirais",
		"itrappedi",
		"ibipolarbears",
		"omfgabriel",
		"dexen",
		"99763434",
		"travis2209",
		"SexyRexy",
		"Pande",
		"spidermat1",
		"kyk",
		"Kedi",
		"peachyx",
		"Nixxor",
		"CAHbl4",
		"atomsk",
		"keite",
		"Sinobis",
		"LastChicken",
		"FreeWare",
		"jeffreyed",
		"Billybobjoe",
		"testgandhi",
		"Hornax",
		"wtfmoo",
		"voyager1487",
		"438602",
		"GGNORE",
		"Godbot",
		"gamla11",
		"Spidicus",
		"841894530",
		"Dogen",
		"satos340",
		"xnoregretz2u",
		"Korea",
		"louiserty",
		"Andpta",
		"onlyvayne",
		"DrYellowhair",
		"k9thebeast",
		"Ahkasha",
		"Tien",
		"hesobig702",
		"jst94",
		"eellis2132",
		"dxterminator",
		"klokje",
		"41204457",
		"BlodIV",
		"kpone53",
		"muffinzz",
		"iKasulol123",
		"hesobig702",
		"ioedaq4",
		"deathnow",
		"Helitjah",
		"ddsq1226",
		"baccano",
		"MrCode",
		"Zonfire",
		"mag4nat",
		"meme00",
		"Kaschi888",
		"cleanthugg",
		"pacci",
		"wildflower1",
		"b4ckst4b",
		"2560633048",
		"gravybaby",
		"Tyvus",
		"rowan",
		"27032704",
		"tayloryo",
		"Skribbs",
		"excalibur80",
		"Besmir",
		"jst94",
		"bnj94",
		"johan95",
		"nathanha20",
		"skiesx",
		"no name",
		"deviruchies",
		"ravenmage",
		"rafaelj",
		"wotafok",
		"Agagapoo",
		"donnerschlag",
		"Hyoubu",
		"Lolopro2",
		"dryice",
		"Ezra",
		"Gervounnet",
		"Stylli",
		"dansa",
		"wocjf7245",
		"leviathanscall",
		"Stupror",
		"tacD",
		"benyuk",
		"jmoney",
		"ilovenng",
		"magicman513",
		"xxgowxx",
		"kamas",
		"ciwolsey",
		"ryot00",
		"aqualake",
		"whatever525",
		"confusingart",
		"ssgleader",
		"vadash",
		"dekaron2",
		"xpantherxx",
		"ciran",
		"Devlin",
		"quartss",
		"Ziphy",
		"Auron2402",
		"Incognito",
		"whisper",
		"burn",
		"ikita",
		"ozzeh",
		"vinsweden",
		"DarkSerenity",
		"CreaShun",
		"jasons",
		"fuli88",
		"subsilver",
		"Il twerkatore",
		"cryo",
		"aristu",
		"Booyakasha",
		"kenlol",
		"arnagos101",
		"milchstrudel",
		"gaship",
		"padlockcode",
		"xycannon",
		"pwner71",
		"Symbolics",
		"Damnation",
		"adamshere",
		"CAHenson",
		"lynx0rr",
		"dblhlx",
		"bbol",
		"Final",
		"im2beast",
		"l00b",
		"jewish",
		"configz",
		"hakuna matata",
		"Vivi",
		"Nardwuar",
		"lexoran",
		"soulswiper",
		"Austyn",
		"maestro29",
		"sirdeejay",
		"Hellfish117",
		"yoslo",
		"daniboy14",
		"m1zfortune",
		"l00b",
		"aznhick",
		"heelx",
		"leopard0815",
		"mohky",
		"CyberKiLL",
		"spudgy",
		"lefty",
		"ilikebacon",
		"Fetterlein",
		"Euronymous2",
		"jwat",
		"Prophet122",
		"sloped",
		"The Haze",
		"poindexter",
		"tongamo",
		"dexter morgan",
		"luizbrtgms",
		"Optimize",
		"hackedhacker",
		"9845163",
		"MrSaluto",
		"nunubot",
		"durrfuqq",
		"Jahwe",
		"dervd",
		"nickelass94",
		"DevilDan91",
		"mzamza14",
		"E7T",
		"nab423",
		"toxic 123",
		"Armani",
		"lzx1984319",
		"tianye",
		"schafi",
		"Sonnentim",
		"mashi7",
		"Thegr81",
		"max118",
		"cody1817",
		"kerath",
		"dabhbomb",
		"diassiau",
		"mrleo",
		"xrexis",
		"JohnT717",
		"lodier2d2z",
		"JaNooB",
		"xDJeffxD",
		"RiskinBrisk",
		"fatfurball",
		"Hellsgift",
		"ronkenpops",
		"WonderShotGG",
		"Synchrodoom",
		"Rashidoz",
		"vixemainha",
		"Antoine0049",
		"ragequit",
		"Sid1a",
		"Sid1a",
		"ragequit",
		"swerve",
		"MuffinFTW",
		"diemiserable",
		"catastroph3",
		"jintsure",
		"helloworld12",
		"shingston",
		"rickisme",
		"horchata1",
		"fatmananddy",
		"Hamburger",
		"Tragboo",
		"catbert",
		"g11",
		"skb",
		"Bercley",
		"xthanhz",
		"107220663",
		"azeroth29",
		"Villalobos",
		"Almightythor101",
		"random919",
		"frneo",
		"apple",
		"Johay",
		"SwaggerMan",
		"poopyfarts",
		"Quigley51",
		"H4CK",
		"jedi",
		"fabioc",
		"Jaikor",
		"justbenhere",
		"mr1r15h",
		"Blitz",
		"anto",
		"shiplx",
		"Shrapnel",
		"skippymcc",
		"MrSkii",
		"darkmind",
		"jjclane",
		"Aaronxing",
		"jagovl",
		"r444ge",
		"unknown88",
		"ifaceroll",
		"chatlotta",
		"wingsoffreedomx",
		"Weary",
		"bothappy",
		"dragonne",
		"savagekitty",
		"windowsmediaman",
		"Yeezus",
		"Madgavis",
		"binhbumpro",
		"lancai",
		"manager9090",
		"zderekzz",
		"xbzz",
		"bedozer",
		"lykiel",
		"xzaviour",
		"dollebrekel",
		".vuZe xX",
		"tortelles",
		"Supreme",
		"diogenes",
		"IGO2W4R",
		"nirvana1221",
		"widelove",
		"zp34ker",
		"Projectb",
		"vhgarcia",
		"chester110",
		"bilbeau",
		"ihateoffseason",
		"Ragou",
		"noobstyle",
		"isintom",
		"biggin3",
		"floresrikko",
		"Landliebe",
		"iRes",
		"rohus",
		"138618",
		"Fabolous1",
		"craphere",
		"RoflWaffle",
		"Corkster",
		"jhs0192",
		"sirkouki",
		"widelove",
		"asianone89",
		"Vice2230",
		"chenyao1992",
		"pcharlesaa",
		"RaZ0r",
		"benzoi",
		"yarick7",
		"stone",
		"Reck23",
		"iluvlamastaf",
		"bishopx4200",
		"waffle",
		"passionford",
		"flowth",
		"habibc",
		"Skipo",
		"iljaas",
		"riverevsan",
		"silverfoxwr80",
		"maauh",
		"heavenphenix",
		"norrin",
		"Raistin",
		"inviz",
		"chillr",
		"Deconomic",
		"zero",
		"cbillups205",
		"12122215",
		"Feez",
		"BAWGroup",
		"corvette",
		"arunasz",
		"legendaryvlad",
		"imnocheater",
		"vGLOWv",
		"mrsithsquirrel1",
		"dustinisgod",
		"baubiness",
		"bananaboy",
		"KaNN",
		"RickRubin",
		"blkancients",
		"yadunknow",
		"Patrichuan",
		"Macadelic",
		"Zidroc",
		"moonrise0",
		"quicksnap",
		"gotenks222",
		"xue1987",
		"424665431",
		"icrusnik",
		"tvanderv",
		"chanman1101",
		"everdream2k",
		"nightmare",
		"BeepBeepImmaJeep",
		"klaraku",
		"jtheis",
		"kbcowboy",
		"Sid1a",
		"bryanfails",
		"faithmelt",
		"eppler97",
		"serpicos",
		"dontstephere",
		"griswold91",
		"lefty",
		"Dekker",
		"ceryni",
		"Officerwilson",
		"redclawsnaby",
		"kunam1159",
		"moleymoles",
		"qd121002",
		"henhen32",
		"jimyall",
		"onepunchman",
		"addd",
		"teemodemo",
		"aqrel",
		"jujupie",
		"Ezenemy",
		"maxghall",
		"jkabrito",
		"hummel88",
		"xitalhoco",
		"mosc2k",
		"ohcookies",
		"Fth17",
		"Binary",
		"mrj",
		"magikarp",
		"jokovic",
		"fillipmt",
		"fayhan",
		"rockstiff",
		"emirc",
		"joacy",
		"Enzyme",
		"darktsuboy",
		"Faye Reagan",
		"tolenicc@hotmail.com",
		"alz337",
		"joedai",
		"rene_gahe",
		"Niq22",
		"roflcopteraway",
		"prasinos",
		"g00y",
		"Aressandoro",
		"pyrolinchen",
		"darkscout",
		"acerzocker",
		"LordSN4KE",
		"Radke",
		"bykajmeran",
		"payla7",
		"j2kill",
		"DeepCold",
		"valais",
		"raingul",
		"eggoo",
		"roope242",
		"Kain",
		"metrotyranno",
		"jaemyeong",
		"captainloo",
		"pbp1221990",
		"metatagz",
		"Qiix",
		"shk1263",
		"gsagiwrgos",
		"desomond",
		"elgatosa",
		"Syphrose",
		"caferos",
		"daggercan",
		"goreume",
		"andy4043",
		"shivan",
		"mauz0",
		"Desker",
		"lolgutted",
		"jught",
		"snoopie",
		"consfearacy",
		"shukhrat",
		"shadowbringer483",
		"KRU3L420",
		"drat",
		"pwndoc",
		"BigAll",
		"LordSN4KE",
		"hootman",
		"valoon",
		"Silverisg",
		"tishat",
		"vinc9993",
		"nemesis668",
		"12sniper45",
		"Gangus5567",
		"JamesOcy",
		"MILLE",
		"b4rk0s",
		"AutumnDarkness",
		"bennyrub",
		"SystemC",
		"jmhscout",
		"sirramires",
		"d3dm4n",
		"sverikaco",
		"itzmak",
		"swerve",
		"Lyszczu",
		"Magnusbt",
		"loluufd",
		"Dangdang07",
		"klodeckel",
		"cainisable",
		"Dobby",
		"alter55",
		"hallababa",
		"billypaiva",
		"zkfla2",
		"Chris787n",
		"oldirtybizza",
		"keystone990",
		"BinaryM",
		"widelove",
		"Hydraulic",
		"Debona1r",
		"Jintsure",
		"jenspro",
		"Core Target",
		"midorfeed",
		"wtfbabe",
		"hammey345",
		"canthandleme",
		"xcxooxl",
		"Redux",
		"cdog123",
		"ellaw",
		"masterries",
		"iBlackReaperHD",
		"gosurisor",
		"errtu",
		"mrwong",
		"willy",
		"didac",
		"11pawlo",
		"awfulgame",
		"spow84",
		"shape16",
		"shaftknight",
		"lolcheater",
		"uhndead",
		"TABtastic",
		"milliardo",
		"jeem",
		"cromer",
		"lzx1984319",
		"olorin",
		"madowden",
		"sgamez1",
		"mithqt",
		"yanhua",
		"lzx1984319",
		"Jus",
		"Schnaps",
		"pumkinjoe",
		"Wervelwind",
		"nldeamon",
		"SexCatt",
		"chriss",
		"SuperSolution",
		"beaver1214",
		"puffinsmuggler",
		"arikation",
		"aceability53",
		"Nosferatu922",
		"neoblack",
		"yuri12344",
		"popnik90",
		"Jikura",
		"mattilivo",
		"luanknox",
		"dmassa",
		"Wertyl11",
		"AmonOne",
		"DannyX",
		"edr1337",
		"lollirenzo",
		"myxa",
		"tarathiel",
		"babacool",
		"pappsen",
		"raven59669596",
		"zestia",
		"rkem",
		"scattaman",
		"PikaBoo",
		"lefty",
		"Sine",
		"haoping1",
		"kingslash22",
		"lezbro",
		"Xalie",
		"soslw123",
		"mvv12",
		"pxil",
		"enishi2712",
		"slmmurat1995",
		"idzyy",
		"zekuromu",
		"hinamizawa",
		"Blaze091",
		"63777377",
		"stinyg",
		"Criesisangel",
		"Bebec12",
		"bbrouwer",
		"Brunon57",
		"Bacchus",
		"travodo",
		"ruleroy",
		"ashaik",
		"1061765650",
		"joel1975",
		"martgaj",
		"Anorise",
		"takal57",
		"marist",
		"rewind",
		"olisky",
		"cdsc222",
		"Crumsa",
		"Strewberry",
		"andy123",
		"yigoyar",
		"coldifre38",
		"soycracka",
		"leon963",
		"locdogh",
		"yulizhi110",
		"PdB",
		"zsoka",
		"pifajka100",
		"nile",
		"jason1742",
		"Volkanyx",
		"raz",
		"zzxxcvcv",
		"ruku",
		"AzurMed3",
		"araaj",
		"mozg12346",
		"harbinger3",
		"lunatix",
		"Travisty",
		"741865447",
		"a100137039",
		"TheNox",
		"rolyftw",
		"BigBudda87",
		"dustinlenguyen",
		"grafarco",
		"wonderingboy",
		"derpherp",
		"0pisNotGhey",
		"zhuqiurun",
		"catcatchoi",
		"big mikey",
		"productoffallout",
		"adamspag",
		"missiles93",
		"pharack",
		"randal",
		"straf",
		"ace1369",
		"xxjpxx",
		"abbystabby",
		"humpex",
		"bao256",
		"koala55",
		"BNegetive",
		"varunen",
		"nicaboy",
		"aruziel",
		"mybadmybad",
		"hunterhunter",
		"grjkos1a",
		"huminzi123",
		"sylon",
		"ijustlivehere",
		"SilverKinG",
		"4zh",
		"Guest599",
		"SWindrunner",
		"nateb04",
		"str3zi",
		"malotesumur",
		"chrisjke",
		"faenta",
		"sk8ter",
		"fluicity",
		"tschakke",
		"Serenity",
		"link21",
		"mekrenon",
		"mw2pewpewpew",
		"lifei",
		"wadekendall",
		"henhen32",
		"allstar69",
		"ANewForumName",
		"silentscope",
		"ratodeporao",
		"neskes",
		"tony",
		"TreeWood",
		"erickho123",
		"Jahai",
		"dclolz",
		"allone123321",
		"pzyko",
		"halibel20",
		"innriwins",
		"shaddowlink",
		"CLG Aphromoo",
		"muranmana",
		"tomelli8",
		"Tech",
		"damein1781",
		"javierfry97",
		"jocena313",
		"skyhawkleague",
		"thingkiller",
		"sneakyfinger",
		"mithqt",
		"jaxy",
		"steone",
		"Weckso",
		"Galaxix",
		"XYZ",
		"wucong2202",
		"dale rossman",
		"Without Silence",
		"Bugmenot1337",
		"wafflehead",
		"SergioMiguel",
		"playabletm",
		"ripnn",
		"was2",
		"frikaa",
		"bakesan",
		"lemonjuice",
		"Paron1",
		"cosmic9619",
		"flamehf",
		"donleon",
		"jtgjustin",
		"nooma42",
		"gosurisor",
		"sinelys",
		"eliteis",
		"kristianpike",
		"Alex79001",
		"imathhater",
		"ttoast",
		"mfzb",
		"fter44",
		"sometimesz",
		"ven0m",
		"wushon12345",
		"JordanMoney",
		"27002764",
		"minikiller",
		"andreluis034",
		"george",
		"mrdoom123",
		"xx22xx",
		"kry0",
		"spidermaxx",
		"shockerton",
		"DarkShifter",
		"MacKay",
		"soom32",
		"TempAccount",
		"pimpstick",
		"Lumi",
		"jokerad123",
		"denizb28",
		"anekraf",
		"mage0wnz216",
		"Diabolical66",
		"ragequit",
		"desperatepower",
		"viper2g1",
		"bessworth",
		"blackbloggo",
		"ajporter93",
		"cedricdu94",
		"Fyx",
		"iJunaid",
		"kyle22326",
		"Downloadi",
		"xFelix",
		"tedyreinoso",
		"thizz4win",
		"gilberto_san",
		"heybigboy5",
		"spectralzz",
		"13713313185",
		"kennyt1001",
		"mk4t",
		"c00kie",
		"Sarodare",
		"ducnam",
		"xolieo",
		"eyyam",
		"nisi2k11",
		"Peterlulz",
		"Igzz",
		"odunsevici",
		"Elizargh",
		"mervinpoh",
		"kyomitsu",
		"ramoreno",
		"q5588634",
		"twerpz",
		"purem5",
		"DatStankyPete",
		"skejtjan",
		"ajbinky",
		"rezilx",
		"JazzyJazz77",
		"merosoko",
		"Tommmy_zL",
		"astrostar",
		"YoloQueue",
		"Ricky227",
		"bol123",
		"Teargas111",
		"espron",
		"Vysil",
		"omgitswhite",
		"Talentboy1",
		"keydox",
		"zombiearmy",
		"wizzywow",
		"nadimbaba",
		"malice",
		"Kolmisai",
		"wolfrain62",
		"teyreach",
		"hfs1147",
		"craig",
		"Mister Sir",
		"banksy135",
		"mao2u",
		"nibirue",
		"shergal",
		"mtmoon",
		"maxmax2",
		"ninjart",
		"jorhje",
		"hied",
		"087028",
		"jigglypuff",
		"imahacker",
		"Sokk",
		"urbanhazard",
		"Xenadus",
		"zamou",
		"mongerr",
		"ad3das",
		"mrkey484",
		"killm0re",
		"Nfinite",
		"sysy5024",
		"infa11ib1e",
		"Darkood",
		"heel",
		"Beorn1988",
		"TheFieryTaco",
		"phadeb",
		"wert12yu",
		"dmil",
		"easyhiv",
		"wouter2203",
		"Tux",
		"Slymenstra",
		"170830712",
		"itzneil",
		"itzneil",
		"znudee",
		"lol3c",
		"Marco123",
		"Someguy",
		"TheSpecialOne",
		"Sn1p3r",
		"basicallysoup",
		"Zem",
		"Carnegie",
		"dannyde",
		"DiFusioN",
		"monster255",
		"darkness",
		"terminalradar",
		"Kyuiki",
		"Sanej",
		"airsly",
		"ItsGwid24",
		"AwNz25",
		"pyryoer",
		"re4l1ty",
		"angel1211",
		"allantruong",
		"korknob",
		"brunosla",
		"Flower",
		"Nezyc",
		"joliter",
		"tseveng",
		"korknob",
		"19chris9",
		"edd",
		"lewwsrodmalp",
		"scrapmetal",
		"turnzdatburnz",
		"sorieus",
		"greymachine",
		"slaves98",
		"birillo5",
		"narc",
		"hiujie",
		"psytrumpet",
		"songtong",
		"Scruffy90",
		"lincoln",
		"spin3x",
		"810963369",
		"meuovo",
		"xorchid",
		"mcblaber",
		"copains",
		"brokencyde",
		"najdjel",
		"rawrlol",
		"baronk",
		"pcplayer",
		"Dalian",
		"plaras444",
		"t0mm",
		"522868600",
		"DraloX",
		"rara1987",
		"799274312",
		"nikmic1",
		"alahmed94",
		"Ppa2k20",
		"kelpo",
		"neocortex18",
		"cyan3290",
		"yokokoyo",
		"mitchm8",
		"bluntmonkey",
		"Someguy",
		"tragedy",
		"binqizhilian",
		"bbman2007",
		"arorawish",
		"abc8902",
		"Bl4Ck",
		"w85159033",
		"Nerdrenx",
		"41881144",
		"ninjart",
		"raiii",
		"JavaNoob",
		"zpyro1",
		"145032",
		"derpthesauce",
		"Lienniar",
		"jty0102",
		"Teima",
		"andythesk8r",
		"malekithrules",
		"Haex",
		"yungege",
		"beliaar12",
		"59398515sx",
		"laomai",
		"mvilera",
		"execution527",
		"sovngarde",
		"q524619119",
		"Skyzor",
		"bledi13",
		"Bisuone",
		"yaymosz",
		"klodeckel",
		"792007467",
		"sjonnyboy",
		"huzoid",
		"El Jakeio",
		"Jeneralen",
		"vapingpbr",
		"kabasa",
		"Sash21234",
		"iluz0r",
		"symox",
		"jrince",
		"yoyo11215",
		"jinhokim1129",
		"hunter35193",
		"MoopGod",
		"Llamabanana",
		"hoof24",
		"xiang5188",
		"vipgogo4",
		"Zyphressss",
		"tali0206",
		"77544550",
		"meiam",
		"ywest",
		"taka9999",
		"sevenjiang",
		"Ojay",
		"holyraider",
		"DAGR8ONE",
		"InjectionDev",
		"lommon",
		"jsteez123",
		"rys",
		"bolczyk",
		"NameX",
		"Rubble0Bubble",
		"challenger12345",
		"265755",
		"blixt111",
		"thirev",
		"xSpaceJack",
		"NoJoke",
		"HaZe",
		"dirty",
		"ruce1984",
		"gyid",
		"Tobaunta",
		"royalflushhd",
		"610226690",
		"show0",
		"botele",
		"rkoooo",
		"jackedupjonesy",
		"MathiasAaen",
		"jsmb768ypl",
		"ikidyouso",
		"bollovefeel",
		"tulle",
		"Midi12",
		"rykerx",
		"rykerx",
		"egal",
		"daddymarod",
		"cyberlol",
		"zeroreflector",
		"peterpansen",
		"tjcksdl",
		"wdac456123",
		"TheHut",
		"slicer502",
		"Greywolf",
		"lqchaye",
		"Falco111",
		"Makex3",
		"silver001",
		"ki88318808",
		"dunkin",
		"Paarth",
		"carules",
		"grutamu",
		"xuziwen77777",
		"Ezio",
		"chankapo119",
		"mirgeeq",
		"Johannessch",
		"lambite",
		"zomgkevin",
		"RebelCamel",
		"Robert29541",
		"wmunny",
		"rawrmilyid",
		"decal",
		"hughes77",
		"shinwoojin",
		"xxevilkingxx",
		"IamAJ",
		"begodon",
		"karolon001",
		"fackman",
		"Fuggi",
		"SHzIMBA",
		"Dierace",
		"Frymi",
		"InterestingBazinga",
		"gustavofelix999",
		"elretardlol",
		"brandonjruiz",
		"Risengarth",
		"Grrrt",
		"dhasselhoff",
		"Mostwanted",
		"xtechnique",
		"layonhands",
		"Opaque42",
		"jhonx21",
		"Apraxia",
		"wanna_duo",
		"Superx321",
		"awmking1475",
		"kingdomofwin",
		"GT3",
		"Ryosu",
		"hhyyyyyh",
		"jj1232727",
		"Panda Propaganda",
		"ALveron",
		"862847",
		"hasan9218",
		"jagric",
		"matheusdrago",
		"michal91",
		"Enix03",
		"drugg2",
		"luciid1",
		"erikcelander",
		"xruinfx",
		"lunatech",
		"shadowx23",
		"hack100",
		"l33tabix",
		"longhaitac",
		"papimouillote",
		"Modako",
		"nedaking",
		"kenta901",
		"mush",
		"mrkwkns",
		"xxbdonxx",
		"elysium",
		"boblikes",
		"delix1993",
		"BBSexychick",
		"rikkal",
		"pierce521",
		"wujianhui",
		"himynameis1010",
		"coffee5800",
		"n0ne",
		"slipnerd",
		"confidentialityspice",
		"csoffos",
		"PostMeridiem",
		"Bauby",
		"pxpc2",
		"hawynstud",
		"mus",
		"taylorswift",
		"jtathax",
		"MasaKor",
		"rubati",
		"kangarooman",
		"midgetfreak",
		"rockadude91",
		"asherrrr",
		"trustxnobody",
		"hamletarcher",
		"Deliriumsoft1",
		"IPFR33LY",
		"Gooby",
		"k4zz0r",
		"flexosoph",
		"xsottel",
		"Mikkeson",
		"detavn",
		"Nermers",
		"elcouchpotato",
		"DrCoXx",
		"Armageddon",
		"71889988",
		"barbs1424",
		"alahmed942",
		"changster109",
		"ilyas112",
		"snoomrpower",
		"solokazama",
		"lol2thisdude",
		"impactful",
		"ImCrave",
		"tromatic",
		"melvanis",
		"s1rragealotuk",
		"hedali",
		"sleeyp",
		"Jadnor",
		"hidevin",
		"napix",
		"Lil Fire Imp",
		"jrince",
		"rubix",
		"carty",
		"hungerjohnson",
		"37782055",
		"NAhlers27",
		"Dallafina",
		"Solo2345",
		"stray",
		"chatcher8806",
		"Xerocide",
		"Dyrone",
		"naow",
		"3SKIMO",
		"demonbushido",
		"masterlight",
		"Ironhidepb",
		"make a wish",
		"nathannathan11",
		"tozededao",
		"aligator",
		"Sharp6677",
		"fredini",
		"keksil",
		"arlo56",
		"splicus",
		"tke19933",
		"Imsmurfinlulz",
		"anheejun227",
		"di0nysos",
		"blumarue",
		"youri911",
		"Franske82",
		"AliceSaka",
		"linkydink",
		"Kusoo",
		"jtathax",
		"synnful1",
		"Vayne",
		"DonGavlar",
		"conco",
		"heartlemon",
		"bertie123",
		"blackrose180",
		"magazorxx",
		"jinear",
		"DatAzn",
		"cbonds",
		"markapril",
		"hazelwood",
		"sandholdt",
		"6233161",
		"blackflagz",
		"Maj1kal",
		"turt",
		"qazqsx",
		"future901",
		"michaelcubel",
		"leetjerk",
		"Sida",
		"encore707",
		"yungpolak",
		"SovietRussiaSam",
		"petersch",
		"reapz",
		"bozzer85",
		"kszef",
		"zhangxc1989",
		"revolltz",
		"woyaojiasu",
		"IchTreffeNix",
		"leqt",
		"cnbmanajj",
		"damuffinator",
		"markcato95",
		"goggles",
		"gldx",
		"cluthu",
		"iamnot",
		"arnagos101",
		"espial",
		"Dall38",
		"Discord",
		"shinwoojin",
		"shinwoojin",
		"yudi",
		"Cake",
		"DeathAngel",
		"yeshao",
		"thenitrozyniak",
		"7upDEEN",
		"TwistedAU",
		"ggwp",
		"saben12345",
		"b3rz3rk",
		"Artibaba",
		"lassiemeow",
		"phil261",
		"challenger12345",
		"brnzao1",
		"FernzY",
		"993280000",
		"rokutukas",
		"kelwynn",
		"Ne0x",
		"nexusg7",
		"blumarue",
		"lolatu",
		"velo",
		"oldwars3d",
		"fizzletron",
		"EzioAuditore",
		"vyktym",
		"frd",
		"Amigo",
		"dewjosh",
		"valuta",
		"504286558",
		"zspritez",
		"TheDude",
		"shinypie",
		"sharps",
		"Cuppedkake",
		"aandre",
		"rinmom94",
		"giesouf",
		"shtevenish",
		"Notch",
		"leffeftw",
		"ednasil",
		"lucasrpc",
		"zhadowmarkuz",
		"Hardwood",
		"stewienana",
		"babysittersdead",
		"NorTroll",
		"Venuse69",
		"markapril",
		"operah11",
		"nans31",
		"devide",
		"Neonblue1121",
		"n0t1nclud3d",
		"Kobav",
		"codebrew",
		"whatefang",
		"gainnn",
		"tatanka17",
		"Mrags",
		"nutzchopper",
		"stupididiot",
		"betawarz",
		"bbb1231",
		"ego",
		"kleeston",
		"try brother",
		"Opaque42",
		"qq1257800056",
		"feldyn",
		"Doozee",
		"Qznechik",
		"RG101",
		"zsweet5",
		"changerman",
		"PiTi",
		"biel1332",
		"gamla11",
		"whyy",
		"luxis797",
		"PacoTai",
		"orixi",
		"Exodus885",
		"libj44",
		"Rectan",
		"ilikeleague123",
		"jsjpop",
		"Marquess",
		"Xiloror",
		"RobertRP",
		"ykg4144",
		"bestplox",
		"jtambo",
		"Snowpup",
		"nekros",
		"thanh1344",
		"sticky",
		"sumfool",
		"NextExperiment",
		"skyte",
		"manolis11",
		"Highlow",
		"screech159357",
		"dudedialed",
		"sevealin",
		"snowg3",
		"TommyWoomer",
		"theoneandonly",
		"rainz",
		"MaasMan",
		"lallexl",
		"oms2112",
		"xt0ny",
		"ceedau",
		"heavendust",
		"jjas0n",
		"crazy4ever",
		"Rylee",
		"jasam",
		"moister",
		"Steffan",
		"weitt",
		"RawBrilliant",
		"sithkain",
		"guplo",
		"DWLooney",
		"vankee",
		"345533015",
		"swordslaser9",
		"jhseo980",
		"kemerova",
		"kukillo1990",
		"Albertohh",
		"technox",
		"dbmoore23575",
		"DNA666777",
		"BrandNameBoss",
		"snukums",
		"lavaboy",
		"bsoft",
		"songyi123",
		"Errinqq",
		"nicaboy",
		"CaptSchwann",
		"tofuartist",
		"lassiemeow",
		"133001479q",
		"Samer01",
		"KeyLogger",
		"xnexus",
		"1ScarletBlade1",
		"wirawong",
		"Don Trollolol",
		"g3ntix",
		"mhtjabermht",
		"brioco666",
		"uokm8",
		"nuuge",
		"wouterio",
		"epoc",
		"TheStatemen",
		"imaknificent",
		"AFaQ",
		"celsei",
		"cliqk",
		"boomser88",
		"rejuik",
		"rkddlfkd",
		"omnichromx",
		"sublimebeach",
		"zcy815290",
		"hacky90",
		"douglas2009",
		"gregsbest",
		"704509068",
		"realityz",
		"Mattwoo",
		"isaacpgl",
		"luxifa114",
		"ruichenwang",
		"asd307952376",
		"diggidoyo",
		"xiaoyuqing",
		"mpollywolly",
		"bernx",
		"desilijic",
		"mungaman",
		"FJ0",
		"mag1ccarp3t",
		"ooways",
		"684761",
		"oweraz",
		"soul30",
		"Kickaz",
		"acvip019",
		"itsChrisso",
		"ako1379",
		"bollovefeel",
		"Destructed",
		"otmdgnso",
		"zj19821008",
		"Himizu",
		"aiir3337",
		"fmflex",
		"NiorBors",
		"stryhn",
		"chihong13",
		"funcof",
		"xeleris",
		"kinkywtf",
		"dviolet",
		"Mikesforza",
		"504286558",
		"lillgoalie",
		"Hero1711",
		"alex3057",
		"thick142",
		"LuizKill",
		"toddyrios2",
		"Hecarim",
		"zz4072347",
		"S1m0n3",
		"7thirteen",
		"noobarrow",
		"jimmyflitschi",
		"TheHoac",
		"aristu",
		"woojams",
		"Paradoxel",
		"Skygirl779",
		"z138820770",
		"az2057353",
		"MarmadukeStank",
		"gorod63",
		"azbin123",
		"lnxepique",
		"theinv4sion",
		"Blubsi",
		"yunmo123",
		"ABSArah",
		"dlsnal",
		"mefody",
		"sixplus83",
		"refleckz45",
		"yaozi520be",
		"roseyixue",
		"1043225521",
		"MyRivenSuxx",
		"Wurstpelle",
		"corinemokje",
		"0II0",
		"Nightfall",
		"erdem",
		"jouzy31",
		"christom1001",
		"potvaliant",
		"Uninvestigated",
		"cthatcher101",
		"getrow",
		"radeon",
		"NeMiiZz",
		"hhyyyyyh",
		"telah",
		"swagglass123",
		"MrDrSir",
		"gledimrock",
		"sth48",
		"sk8boy",
		"chouw31",
		"skykpt",
		"Botwinn",
		"MikeyC41",
		"Buhlongo",
		"zeolot",
		"Spasik",
		"JayceCombo",
		"strvit",
		"pietje12",
		"derlvix",
		"cryptide",
		"Iblazen",
		"yenik007",
		"brodiev",
		"CzarRazc",
		"dds310",
		"soceya",
		"shin5156",
		"a33170000",
		"stunvn",
		"Flyster",
		"RuEn",
		"crisverde",
		"neoblack",
		"bshift",
		"dylanx22",
		"jerry1336",
		"Duox",
		"aiksu",
		"RaiduBoom",
		"ChrisSetzer",
		"iwe",
		"thick142",
		"Jetvictor",
		"ykg4144",
		"bigpapajoe",
		"BlodIV",
		"289900390",
		"553814385",
		"SpyroPT",
		"xdd654321",
		"Atsumito",
		"zxw459679",
		"soonia",
		"kennytammy",
		"Karolon001",
		"omerakar",
		"ibwubz",
		"snor",
		"xtc1992",
		"Codax",
		"thebadguy",
		"panceparty",
		"ChrisWA123",
		"lolcheat321",
		"surfinguy360",
		"Eredan",
		"travis2209",
		"griswold91",
		"kriptose420",
		"optempo",
		"kriptose420",
		"lokoroxbr",
		"makele0211",
		"Thekevjaa",
		"BERNX",
		"Warp",
		"machajr",
		"igoboom123",
		"johnlai4",
		"otmdgnso",
		"SeanC",
		"asdeak",
		"futuresound",
		"pooce81",
		"Fiddlestix",
		"lhyfifa",
		"Coltenfrese",
		"smoke89",
		"bcipa3",
		"kleksskill",
		"jimyall",
		"C4ss10",
		"totallytrav",
		"jhonx21",
		"treetree",
		"Ekuang",
		"Bugador",
		"Elite4554",
		"NotQuiteDoublelift",
		"Criesisangel",
		"DarkFlame",
		"jaycie02",
		"salvesalve",
		"siRi85",
		"BolTestFr",
		"herpaderpa123",
		"zcy815290",
		"blackguy420",
		"Blaze091",
		"slay_pl",
		"relaxkid",
		"Barbarian3",
		"eleandyl",
		"sunghwan1304",
		"Derang3d",
		"p0p0p0p",
		"ln96281",
		"wukeokok",
		"gametimer",
		"Uday71",
		"soundgraphx",
		"USMEZZANINES",
		"522868600",
		"Yeshy",
		"DatAzn",
		"dblader987",
		"apersonliving",
		"xww84912",
		"yooperup1",
		"Asger.",
		"CheatingDad",
		"caferos",
		"anheejun227",
		"tehmax",
		"Uhndead",
		"Clamity",
		"BNegetive",
		"dubdub",
		"barbs1424",
		"Google",
		"Drijon2",
		"Szymon123",
		"Phebos",
		"Chipper308",
		"sasci58",
		"Yogoo",
		"tigers2020",
		"killm0re",
		"martiniano",
		"Hockey85",
		"sasci58",
		"Johnooboyoo",
		"oloolloo",
		"zachery",
		"87880306",
		"mgstrom",
		"capa",
		"mimi12138",
		"raiderrey",
		"a608293",
		"a110",
		"bshift",
		"Heeybrother",
		"841894530",
		"haitashe",
		"E7T",
		"KwP1169",
		"vinsweden",
		"bedaky",
		"6933857",
		"nubik1",
		"marchiselli",
		"albi21",
		"Groundhack",
		"dirki3",
		"GeeNeee",
		"creationbot",
		"woddystory",
		"chyeahdog",
		"Drakediesel",
		"pepsimaywest",
		"coldlust",
		"erickho123",
		"kopo91",
		"Frosttfire",
		"wtf2020",
		"lethalshot",
		"Sphex007",
		"pikalo64",
		"dukk",
		"ruichenwang",
		"sgt flamingo",
		"woddystory",
		"chyeahdog",
		"Drakediesel",
		"pepsimaywest",
		"coldlust",
		"erickho123",
		"kopo91",
		"Frosttfire",
		"wtf2020",
		"Sphex007",
		"pikalo64",
		"dukk",
		"ruichenwang",
		"Velo",
		"sgt flamingo",
		"s1rragealotuk",
		"Befferke",
		"TerribleLuck",
		"NidaleeSama",
		"Semyon",
		"II R4MP4G3",
		"exeat",
		"yardg",
		"97519250",
		"Swift Xi",
		"matti815",
		"Todelu",
		"jaywang",
		"qwe8728134",
		"pwnage12",
		"Ganksta2012",
		"Veeox",
		"SoulWeaver",
		"aiszu",
		"synop",
		"Tmedlin",
		"xellosx",
		"DontTouch",
		"huckleberry911",
		"THK",
		"1aznboii22",
		"virgofeng",
		"KaiyoreDesu",
		"48588484",
		"devek",
		"mankun",
		"Journeyz",
		"tg316",
		"godlikemate",
		"funns56",
		"Xroz",
		"yippy13",
		"cheeto",
		"riGGz0r",
		"syyke",
		"a8866645",
		"dehelloo",
		"Droghyz",
		"Augustis",
		"snorlaxlau",
		"cheeto",
		"riGGz0r",
		"syyke",
		"a8866645",
		"dehelloo",
		"Droghyz",
		"Augustis",
		"snorlaxlau",
		"Martin",
		"tienermoeder",
		"nxnew",
		"DarkNaxos",
		"donleon",
		"Venuse69",
		"Lilgook",
		"achoi",
		"kesattinh77",
		"hyakin",
		"trustzin",
		"Shamao",
		"CaptainZix",
		"pbeeu",
		"achoi",
		"chron",
		"kisikisi",
		"Luigi89",
		"l0ST",
		"RickStallion",
		"k1xstinkt",
		"Brutallogic",
		"Alex10025",
		"nigwestclan",
		"Tyler1993",
		"0815Hack",
		"djmick",
		"12sniper45",
		"santacreu",
		"luxifa114",
		"anjimlio2",
		"aydos",
		"DenzohD",
		"Zepar",
		"brindeds",
		"Victorw1",
		"gastan",
		"SkankTank",
		"Animal",
		"arnaud_177",
		"ruolinmingjiang",
		"godlikemate",    
		"guillaumepwn",
		"bertieboy",
		"Watermoon1212",
		"DjibrilDiallo",
		"Thrill Drill",
		"aloflado",
		"Gigofar",
		"diliodi",
		"Ovvner",
		"Alex10025",
		"koidada",
		"Transistory",
		"farshmak",
		"sephorus",
		"avanteCURVAsul",
		"shortyboy",
		"glykjt",
		"vexio",
		"ne0burn3r",
		"s3t1k",
		"hht4699885",
		"inconspicuous",
		"Guadostar",
		"staxx",
		"qwert117",
		"eirikhr",
		"DKorotkov",
		"Hanablatt",
		"1einstein",
		"Tibadoc",
		"thebanned4life",
		"Gorronox",
		"MAROC",
		"slickestnick",
		"mucio1g",
		"Inygma",
		"jiawei6663819",
		"craddue",
		"kaibabiak",
		"yorgizord",
		"qq794481",
		"damanfra",
		"Rikal",
		"countcount",
		"skaterkid212",
		"nellybr",
		"people person23",
		"carymyteam",
		"Prejudicial",
		"alchack",
		"muffins592",
		"Athena",
		"Dragoron",
		"3524964",
		"upandup",
		"faction",
		"spfcneto",
		"bayzo",
		"askar",
		"dennisistderbestecheater",
		"a6855773",
		"writeitdown",
		"Nyvis",
		"naomeazies",
		"srxymb",
		"coleman",
		"CryzGaming1",
		"ihateoffseason",
		"luxcas02",
		"BooKuu",
		"Syax",
		"Nestade",
		"snowboard4life6",
		"doug33",
		"Dclolz",
		"niconhan",
		"fuckthis",
		"mr1r15h",
		"fallofgod530",
		"Activadee",
		"nDvich",
		"matizbest",
		"carloslisto11",
		"shinzhu",
		"That0n3Guy",
		"darkmals00",
		"Misso",
		"g0215020",
		"stedak1ng",
		"darki2k",
		"buttercup",
		"Summonist",
		"yoloswag27",
		"iSony",
		"lyte27",
		"Matheushrr",
		"Homealone",
		"ttoast",
		"hornax",
		"vagmole",
		"bolvipp",
		"Expliciate",
		"Braiton",
		"Stream",
		"gasgasguven",
		"mexicaan",
		"botdevice",
		"1vert",
		"Zekuromu",
		"boymatkieng",
		"iiNverTT",
		"Marukaz",
		"sensenq",
		"kinnu",
		"shinzhu",
		"Turkar",
		"lzx1984319",
		"vidiospace",
		"1874790585",
		"c4lly",
		"howle",
		"haxor95",
		"koruny",
		"apenspuug",
		"Parad0xi",
		"puppydogz",
		"shogunrua",
		"Danny1994",
		"ghostsniper0101",
		"Mcgyyver",
		"robbycz",
		"aduzgr",
		"Bombomkiller",
		"koa257",
		"zecapagodinho",
		"NotDyrus",
		"ralphlol",
		"kobrakhan69",
		"burgermund",
		"ThEoBaLdO",
		"Saaz",
		"Bossgen",
		"FerhatSY",
		"vanevo",
		"battler624",
		"a6410799",
		"Rikustaka",
		"LoLmisc",
		"curtykins",
		"dray",
		"goht0721",
		"OSDE",
		"levomi",
		"DravenHeart1337",
		"neonoi",
		"rami789",
		"Ozzy3g",
		"sirarduin",
		"Heimy",
		"vescor",
		"edgar92",
		"Bim411",
		"tilt",
		"chesters25",
		"donmiraglia",
		"CuttyFlam",
		"JeyZziI",
		"magus5200",
		"Moirai",
		"mpall",
		"unfaehig",
		"Elderin",
		"704509068",
		"wardstone",
		"ShadowXS1",
		"illuzion",
		"mydadmightbegay",
		"nowak111",
		"dart127739",
		"yuven",
		"Thomas1249",
		"Nomgnus",
		"wowalid",
		"KoBlao",
		"toptier",
		"efeerol",
		"Austipain",
		"Radeborn",
		"qwoo",
		"Sabiancym",
		"powerfetish",
		"d475719",
		"iAmAvarice",
		"green231035",
		"zpitty",
		"Legendaryy",
		"momo124",
		"psychostyle",
		"smallfruit",
		"q7leo",
		"arwen1987",
		"JDE702",
		"gldennishk",
		"ibizatwist",
		"Taraflex",
		"ILoveAllTheDevs",
		"hadacusan",
		"cybeldia",
		"mtcfreeman",
		"zpitty",
		"HaveFaithInMe",
		"Eisenberg",
		"jaja095",
		"supermanx23",
		"DJ Mateo 904",
		"yerych",
		"Dibes",
		"jakedd1512",
		"sugarplumjahn",
		"destinydejaomeir",
		"pokerface1551",
		"soso416",
		"Askeo",
		"shran",
		"FraRef",
		"teecolz",
		"DezeD",
		"vyzx",
		"maikel",
		"1061765650",
		"wuyuetian",
		"t1mo",
		"toasted",
		"marlo19",
		"PVZ23",
		"sirona",
		"deadrat",
		"michi0987",
		"TrainjahX",
		"9cix",
		"wringley",
		"jamestaylor",
		"basketboy",
		"Bunyam",
		"Gtv123",
		"aseikin",
		"everybears",
		"dom555",
		"iqwej",
		"tangyyc1",
		"KhaosKnight",
		"rongreat100",
		"fuhuixiao",
		"Redmist1337",
		"Saxlecha",
		"dast1212",
		"saddelol",
		"STAGE",
		"alexisalive",
		"mettbrot",
		"jakedd1512scam",
		"ukantaim125",
		"mao2u",
		"carry2u",
		"sasukeguga",
		"fdpfdpxd",
		"jj930216",
		"simjoell",
		"mantracker",
		"Stuto",
		"alansolo75",
		"bonaqua44",
		"fizzl",
		"processhacker",
		"Xentrice",
		"shawn1218",
		"Playingleague674",
		"chrisair",
		"lolos1986",
		"Mathew762eml",
		"ThyFate",
		"azertyrogue",
		"nosfera2",
		"sabot",
		"thugo",
		"DeeZy",
		"shawn24",
		"abcsyy",
		"kchan014",
		"1607536077",
		"lolermoler",
		"Cyberhero23",
		"phuongvl2016",
		"Ruizo",
		"smithmagic",
		"7170",
		"watzefok",
		"rikyuchan",
		"realdudes123",
		"leebbbb",
		"Frewor",
		"RedArmy",
		"Manolo8",
		"eram",
		"XtremaVX",
		"ROPE",
		"Cainisable",
		"sharppotato",
		"w0lfw00d",
		"Wrabit",
		"ldyAnton",
		"truxranger2",
		"vaskokrasi",
		"Takagii",
		"Barbwire88",
		"Howli",
		"superbsmile",
		"bubblegum96",
		"tya2005",
		"Neo",
		"ibizatwistscam",
		"BrownsFan",
		"Southrn",
		"TheOnlyDuck",
		"sandroweng",
		"farkam135",
		"elohel",
		"aher",
		"Rajom",
		"robnessmonster",
		"undeadarmy2012",
		"w0lfw00d",
		"ThanatosMORTIS",
		"dudes903",
		"chinamax",
		"Fireworrks",
		"smartchoices",
		"cpanel10x",
		"linuxtheblack",
		"davixx500",
		"Rekral",
		"rygryg",
		"d4026370se",
		"pb345",
		"venomteddy",
		"Vizualizm",
		"moonpause",
		"meowimeow",
		"pixzel",
		"jjclane",
		"baconese",
		"gs2",
		"jzf",
		"geralt20",
		"hannahaff",
		"infozeta",
		"num1pkfutur",
		"adamhacks",
		"LegitCupCakes"
	}

	for _, usr in pairs(usrs) do
		if GetUser():lower() == usr:lower() then
			PrintSystemMessage("Valid User Found. Loaded as "..(VIP_USER and "VIP" or "Non-VIP").." user.")
			AddTickCallback(MainTick)
			if not DoneInit then
				Init()
				function _Orbwalker:Orbwalk(Target)
					if MyHero.CanOrbwalk then
						if self:CanOrbwalkTarget(Target) then
							if self:CanShoot() then
								MyHero:Attack(Target)
							elseif self:CanMove()  then
								MyHero:Move()
							end
						else
							MyHero:Move()
						end
					end
				end
				return 
			end
			return true
		end
	end
	PrintSystemMessage("Invalid User.")
	return false
end